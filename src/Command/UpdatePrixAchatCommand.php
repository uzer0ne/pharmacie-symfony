<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\MedicamentBdpmRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-prix-achat',
    description: 'Calcule et met à jour les prix d\'achat (PFHT) et de vente des produits à partir des données publiques (BDPM)',
)]
class UpdatePrixAchatCommand extends Command
{
    public function __construct(
        private ProduitRepository $produitRepo,
        private MedicamentBdpmRepository $bdpmRepo,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Mise à jour des Prix (Vente TTC et Achat HT) depuis la BDPM');

        $produits = $this->produitRepo->findAll();
        $total = count($produits);

        if ($total === 0) {
            $io->warning('Aucun produit trouvé dans le catalogue de la pharmacie.');
            return Command::SUCCESS;
        }

        $io->writeln("Recherche des correspondances CIP13 pour $total produits...");
        
        $progressBar = new ProgressBar($output, $total);
        $progressBar->start();

        $updatedCount = 0;
        $notFoundCount = 0;

        foreach ($produits as $produit) {
            $cis = $produit->getCodeProduit();

            if (!$cis) {
                $progressBar->advance();
                continue;
            }

            // Recherche du médicament officiel via le CIS (le code_produit fait 8 chiffres et correspond au CIS)
            $bdpm = $this->bdpmRepo->findOneBy(['codeCis' => $cis]);

            if ($bdpm && $bdpm->getPrixRemboursement() > 0) {
                $prixPublicTtc = (float) $bdpm->getPrixRemboursement();
                
                // 1. Mise à jour du Prix de Vente TTC officiel
                $produit->setPrixProduit((string) $prixPublicTtc);

                // 2. Rétro-calcul du Prix d'Achat HT (Prix Fabricant HT)
                // En pharmacie, la TVA sur le remboursable est de 2.1%
                $prixHt = $prixPublicTtc / 1.021;
                
                // Calcul simplifié de la marge dégressive lissée française (~26% de marge commerciale en moyenne)
                // PFHT = Prix HT - Marge Pharmacien
                $prixAchatHt = $prixHt * 0.74; 
                
                $produit->setPrixAchat((string) round($prixAchatHt, 2));

                $updatedCount++;
            } else {
                $notFoundCount++;
            }

            $progressBar->advance();
        }

        $this->em->flush();
        $progressBar->finish();
        
        $io->newLine(2);
        $io->success([
            'Mise à jour terminée !',
            sprintf('%d produits ont été mis à jour avec les prix officiels de la BDPM.', $updatedCount),
            sprintf('%d produits n\'ont pas été trouvés dans la BDPM (Parapharmacie ou OTC sans prix réglementé).', $notFoundCount)
        ]);

        return Command::SUCCESS;
    }
}
