<?php

namespace App\Command;

use App\Entity\MedicamentBdpm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'app:import-bdpm',
    description: 'Télécharge et importe les données officielles de la BDPM (ANSM) dans la base de données',
)]
class ImportBdpmCommand extends Command
{
    // URLs officielles de la BDPM — Base de Données Publique des Médicaments (ANSM)
    // Source confirmée : https://base-donnees-publique.medicaments.gouv.fr/telechargement
    private const URL_CIS   = 'https://base-donnees-publique.medicaments.gouv.fr/telechargement?fichier=CIS_bdpm.txt';
    private const URL_CIP   = 'https://base-donnees-publique.medicaments.gouv.fr/telechargement?fichier=CIS_CIP_bdpm.txt';
    private const URL_COMPO = 'https://base-donnees-publique.medicaments.gouv.fr/telechargement?fichier=CIS_COMPO_bdpm.txt';

    private const BATCH_SIZE = 500;

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('skip-download', null, InputOption::VALUE_NONE, 'Utiliser les fichiers déjà téléchargés dans /tmp')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limiter le nombre de médicaments importés (pour tests)', 0);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Import BDPM — Base de Données Publique des Médicaments (ANSM)');

        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'bdpm_import';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $fileCis   = $tempDir . DIRECTORY_SEPARATOR . 'CIS_bdpm.txt';
        $fileCip   = $tempDir . DIRECTORY_SEPARATOR . 'CIS_CIP_bdpm.txt';
        $fileCompo = $tempDir . DIRECTORY_SEPARATOR . 'CIS_COMPO_bdpm.txt';

        // ── Étape 1 : Téléchargement ──────────────────────────────────────────
        if (!$input->getOption('skip-download')) {
            $io->section('1/4 — Téléchargement des fichiers BDPM...');
            $client = HttpClient::create(['timeout' => 120]);

            $downloads = [
                ['url' => self::URL_CIS,   'file' => $fileCis,   'name' => 'CIS_bdpm.txt (médicaments)'],
                ['url' => self::URL_CIP,   'file' => $fileCip,   'name' => 'CIS_CIP_bdpm.txt (présentations/prix)'],
                ['url' => self::URL_COMPO, 'file' => $fileCompo, 'name' => 'CIS_COMPO_bdpm.txt (composition/dosage)'],
            ];

            foreach ($downloads as $dl) {
                $io->write("  ⬇  Téléchargement de {$dl['name']}... ");
                try {
                    $response = $client->request('GET', $dl['url']);
                    $content = $response->getContent();
                    file_put_contents($dl['file'], $content);
                    $size = round(strlen($content) / 1024 / 1024, 1);
                    $io->writeln("<info>OK ({$size} MB)</info>");
                } catch (\Exception $e) {
                    $io->error("Échec du téléchargement de {$dl['name']} : " . $e->getMessage());
                    return Command::FAILURE;
                }
            }
        } else {
            $io->note('Option --skip-download : utilisation des fichiers existants dans ' . $tempDir);
        }

        // Vérification que les fichiers existent
        foreach ([$fileCis, $fileCip, $fileCompo] as $file) {
            if (!file_exists($file)) {
                $io->error("Fichier manquant : $file. Relancez sans --skip-download.");
                return Command::FAILURE;
            }
        }

        // ── Étape 2 : Parsing CIS (médicaments) ──────────────────────────────
        $io->section('2/4 — Parsing CIS_bdpm.txt...');
        $medicaments = $this->parseCis($fileCis, $io);
        $io->writeln(sprintf('  → %d médicaments trouvés dans le fichier CIS', count($medicaments)));

        // ── Étape 3 : Enrichissement avec CIP (prix) et COMPO (dosage) ───────
        $io->section('3/4 — Enrichissement avec prix et dosages...');
        $this->enrichWithCip($fileCip, $medicaments, $io);
        $this->enrichWithCompo($fileCompo, $medicaments, $io);

        // ── Étape 4 : Import en BDD ───────────────────────────────────────────
        $io->section('4/4 — Import en base de données...');

        $limit = (int) $input->getOption('limit');
        if ($limit > 0) {
            $medicaments = array_slice($medicaments, 0, $limit, true);
            $io->note("Mode test : import limité à {$limit} médicaments");
        }

        // Vider la table existante
        $io->write('  🗑  Suppression des anciennes données... ');
        $this->em->getConnection()->executeStatement('DELETE FROM medicament_bdpm');
        $io->writeln('<info>OK</info>');

        $io->write('  💾  Insertion en BDD... ');
        $progress = new ProgressBar($output, count($medicaments));
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %elapsed:6s%');
        $progress->start();

        $count = 0;
        $now = new \DateTimeImmutable();

        foreach ($medicaments as $data) {
            $med = new MedicamentBdpm();
            $med->setCodeCis($data['cis']);
            $med->setDenomination($data['denomination']);
            $med->setFormePharmaceutique($data['forme'] ?? null);
            $med->setVoiesAdministration($data['voies'] ?? null);
            $med->setStatutAmm($data['statut'] ?? null);
            $med->setTitulaire($data['titulaire'] ?? null);
            $med->setCodeCip13($data['cip13'] ?? null);
            $med->setLibellePresentation($data['libelle_pres'] ?? null);
            $med->setPrixRemboursement($data['prix'] ?? null);
            $med->setTauxRemboursement($data['taux_remb'] ?? null);
            $med->setSubstanceActive($data['substance'] ?? null);
            $med->setDosage($data['dosage'] ?? null);
            $med->setDerniereMAJ($now);

            $this->em->persist($med);
            $count++;

            if ($count % self::BATCH_SIZE === 0) {
                $this->em->flush();
                $this->em->clear();
            }

            $progress->advance();
        }

        $this->em->flush();
        $this->em->clear();
        $progress->finish();

        $io->newLine(2);
        $io->success(sprintf(
            '✅  Import terminé ! %d médicaments importés depuis la BDPM (ANSM). Mise à jour : %s',
            $count,
            $now->format('d/m/Y à H:i')
        ));

        return Command::SUCCESS;
    }

    /**
     * Parse CIS_bdpm.txt — Colonnes (séparateur tabulation) :
     * 0:CIS | 1:Dénomination | 2:Forme | 3:Voies | 4:Statut AMM | 5:Type proc | 6:État comm | 7:Date AMM | 8:Titulaire | 9:Surveillance
     */
    private function parseCis(string $file, SymfonyStyle $io): array
    {
        $result = [];
        $handle = fopen($file, 'r');
        if (!$handle) {
            $io->error("Impossible d'ouvrir $file");
            return [];
        }

        while (($line = fgets($handle)) !== false) {
            $line = mb_convert_encoding(rtrim($line, "\r\n"), 'UTF-8', 'UTF-8,ISO-8859-1');
            $cols = explode("\t", $line);

            if (count($cols) < 2) continue;

            $cis = trim($cols[0]);
            if (!$cis || !is_numeric($cis)) continue;

            $result[$cis] = [
                'cis'         => $cis,
                'denomination'=> trim($cols[1] ?? ''),
                'forme'       => trim($cols[2] ?? '') ?: null,
                'voies'       => trim($cols[3] ?? '') ?: null,
                'statut'      => trim($cols[4] ?? '') ?: null,
                'titulaire'   => trim($cols[10] ?? '') ?: null,
            ];
        }

        fclose($handle);
        return $result;
    }

    /**
     * Parse CIS_CIP_bdpm.txt pour enrichir avec prix et présentation.
     * Colonnes : 0:CIS | 1:CIP7 | 2:LibelléPrésentation | 3:StatutAdm | 4:EtatComm | 5:DateDecl |
     *            6:CIP13 | 7:Agréé | 8:Remb | 9:TauxRemb | 10:PrixHT | 11:HonoDispensation |
     *            ... (Le prix public TTC est souvent PrixHT + honoraires)
     * On prend la première présentation valide par CIS.
     */
    private function enrichWithCip(string $file, array &$medicaments, SymfonyStyle $io): void
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            $io->warning("Impossible d'ouvrir le fichier CIP : $file");
            return;
        }

        $seen = []; // On ne prend que la première présentation par CIS
        while (($line = fgets($handle)) !== false) {
            $line = mb_convert_encoding(rtrim($line, "\r\n"), 'UTF-8', 'UTF-8,ISO-8859-1');
            $cols = explode("\t", $line);

            if (count($cols) < 7) continue;

            $cis = trim($cols[0]);
            if (!$cis || !isset($medicaments[$cis]) || isset($seen[$cis])) continue;

            // Le fichier officiel est structuré ainsi (à partir de l'index 6) :
            // 6: CIP13 | 7: Agréé | 8: TauxRemb (ex: 65%) | 9: Prix sans honoraire | 10: Prix Public TTC | 11: Honoraire
            $tauxRemb = trim($cols[8] ?? '');
            
            $prixSansHono = isset($cols[9]) ? str_replace(',', '.', trim($cols[9])) : '';
            $prixPublicTTC = isset($cols[10]) ? str_replace(',', '.', trim($cols[10])) : '';

            $prixFinal = null;
            // On privilégie le Prix Public TTC qui inclut les honoraires
            if (is_numeric($prixPublicTTC) && (float)$prixPublicTTC > 0) {
                $prixFinal = number_format((float)$prixPublicTTC, 2, '.', '');
            } elseif (is_numeric($prixSansHono) && (float)$prixSansHono > 0) {
                $prixFinal = number_format((float)$prixSansHono, 2, '.', '');
            }

            $medicaments[$cis]['cip13']       = trim($cols[6] ?? '') ?: null;
            $medicaments[$cis]['libelle_pres'] = trim($cols[2] ?? '') ?: null;
            $medicaments[$cis]['taux_remb']    = $tauxRemb ?: null;
            $medicaments[$cis]['prix']         = $prixFinal;

            $seen[$cis] = true;
        }

        fclose($handle);
    }

    /**
     * Parse CIS_COMPO_bdpm.txt pour enrichir avec substance active et dosage.
     * Colonnes : 0:CIS | 1:ElementPharm | 2:CodeSubstance | 3:DénomSubstance | 4:Dosage | 5:RefDosage | 6:Nature | 7:NumLiaison
     * On concatène les substances si plusieurs par médicament.
     */
    private function enrichWithCompo(string $file, array &$medicaments, SymfonyStyle $io): void
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            $io->warning("Impossible d'ouvrir le fichier COMPO : $file");
            return;
        }

        $substances = [];
        $dosages    = [];

        while (($line = fgets($handle)) !== false) {
            $line = mb_convert_encoding(rtrim($line, "\r\n"), 'UTF-8', 'UTF-8,ISO-8859-1');
            $cols = explode("\t", $line);

            if (count($cols) < 5) continue;

            $cis = trim($cols[0]);
            if (!$cis || !isset($medicaments[$cis])) continue;

            // Nature SA = substance active (vs excipient)
            $nature = strtoupper(trim($cols[6] ?? ''));
            if ($nature !== 'SA') continue; // Ignorer les excipients

            $substance = trim($cols[3] ?? '');
            $dosage    = trim($cols[4] ?? '');

            if ($substance) {
                $substances[$cis][] = $substance;
                if ($dosage) {
                    $dosages[$cis][] = $dosage;
                }
            }
        }

        fclose($handle);

        // Fusionner dans le tableau principal
        foreach ($substances as $cis => $subs) {
            $medicaments[$cis]['substance'] = implode(' + ', array_unique($subs));
            $medicaments[$cis]['dosage']    = isset($dosages[$cis])
                ? implode(' / ', array_unique($dosages[$cis]))
                : null;
        }
    }
}
