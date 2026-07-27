<?php

namespace App\DataFixtures;

use App\Entity\Medecin;
use App\Entity\Mutuelle;
use App\Entity\Patient;
use App\Entity\Produit;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // =====================================================================
        // 0. UTILISATEURS — Comptes par défaut pour chaque rôle
        // =====================================================================
        $usersData = [
            ['email' => 'admin@pharmagest.fr',      'password' => 'admin123',      'roles' => ['ROLE_ADMIN']],
            ['email' => 'pharmacien@pharmagest.fr',  'password' => 'pharma123',     'roles' => ['ROLE_PHARMACIEN']],
            ['email' => 'user@pharmagest.fr',        'password' => 'user123',       'roles' => []],
            // ── Gestionnaire de stock : accès limité au stock uniquement ──
            ['email' => 'stock@pharmagest.fr',       'password' => 'stock123',      'roles' => ['ROLE_GESTIONNAIRE_STOCK']],
        ];

        foreach ($usersData as $data) {
            $user = new User();
            $user->setEmail($data['email']);
            $user->setRoles($data['roles']);
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $data['password'])
            );

            $manager->persist($user);
        }

        // =====================================================================
        // 1. MUTUELLES (5) — Noms et taux de couverture réalistes
        // =====================================================================
        $mutuellesData = [
            ['nom' => 'Harmonie Mutuelle',      'taux' => '80.00', 'contact' => '01 44 83 12 00'],
            ['nom' => 'MGEN',                   'taux' => '95.00', 'contact' => '01 40 47 22 00'],
            ['nom' => 'Malakoff Humanis',       'taux' => '70.00', 'contact' => '01 56 03 30 00'],
            ['nom' => 'MAAF Santé',             'taux' => '60.00', 'contact' => '05 49 34 56 78'],
            ['nom' => 'Groupama Santé Active',  'taux' => '85.00', 'contact' => '01 44 56 78 90'],
        ];

        $mutuelles = [];
        foreach ($mutuellesData as $data) {
            $mutuelle = new Mutuelle();
            $mutuelle->setNomMutuelle($data['nom']);
            $mutuelle->setTauxRemboursement($data['taux']);
            $mutuelle->setContactMutuelle($data['contact']);

            $manager->persist($mutuelle);
            $mutuelles[] = $mutuelle;
        }

        // =====================================================================
        // 2. MÉDECINS (10) — Spécialités réalistes dans l'adresse
        // =====================================================================
        $specialites = [
            'Médecin généraliste',
            'Cardiologue',
            'Dermatologue',
            'Pédiatre',
            'Gynécologue',
            'Ophtalmologue',
            'ORL',
            'Rhumatologue',
            'Pneumologue',
            'Endocrinologue',
        ];

        $medecins = [];
        for ($i = 0; $i < 10; $i++) {
            $medecin = new Medecin();
            $medecin->setNomMedecin($faker->lastName());
            $medecin->setPrenomMedecin($faker->firstName());
            $medecin->setContactMedecin($faker->phoneNumber());
            // L'adresse contient la spécialité + adresse cabinet
            $medecin->setAdresseMedecin(
                $specialites[$i] . ' — ' . $faker->streetAddress() . ', ' . $faker->postcode() . ' ' . $faker->city()
            );

            $manager->persist($medecin);
            $medecins[] = $medecin;
        }

        // =====================================================================
        // 3. PATIENTS (20) — Associés aléatoirement à 1-2 mutuelles
        // =====================================================================
        $patients = [];
        for ($i = 0; $i < 20; $i++) {
            $patient = new Patient();
            $patient->setNomPatient($faker->lastName());
            $patient->setPrenomPatient($faker->firstName());
            $patient->setAdressePatient(
                $faker->streetAddress() . ', ' . $faker->postcode() . ' ' . $faker->city()
            );
            $patient->setDateNaissance(
                $faker->dateTimeBetween('-85 years', '-18 years')
            );

            // Chaque patient a 1 ou 2 mutuelles
            $nbMutuelles = $faker->numberBetween(1, 2);
            $mutuellesChoisies = $faker->randomElements($mutuelles, $nbMutuelles);
            foreach ($mutuellesChoisies as $mut) {
                $patient->addMutuelle($mut);
            }

            $manager->persist($patient);
            $patients[] = $patient;
        }

        // =====================================================================
        // 4. PRODUITS (50) — Médicaments réalistes avec CIP-13 et stocks variés
        // =====================================================================
        $medicaments = [
            // [nom, dosage, code_produit (code interne), prix_vente, prix_achat]
            ['Doliprane',              '500mg',    'DOL500',   '2.18',  '1.10'],
            ['Doliprane',              '1000mg',   'DOL1000',  '2.58',  '1.30'],
            ['Efferalgan',             '500mg',    'EFF500',   '2.35',  '1.20'],
            ['Dafalgan',               '1000mg',   'DAF1000',  '2.72',  '1.40'],
            ['Ibuprofène Mylan',       '400mg',    'IBU400',   '1.93',  '0.95'],
            ['Advil',                  '200mg',    'ADV200',   '3.25',  '1.60'],
            ['Nurofen',                '400mg',    'NUR400',   '3.85',  '1.90'],
            ['Spasfon',               '80mg',     'SPA80',    '2.15',  '1.05'],
            ['Smecta',                '3g',       'SME3G',    '3.60',  '1.80'],
            ['Gaviscon',              '500mg',    'GAV500',   '5.90',  '3.10'],
            ['Maalox',                '400mg',    'MAA400',   '4.75',  '2.40'],
            ['Amoxicilline',          '500mg',    'AMO500',   '3.42',  '1.70'],
            ['Amoxicilline',          '1g',       'AMO1G',    '4.58',  '2.30'],
            ['Augmentin',             '1g/125mg', 'AUG1G',    '7.22',  '3.60'],
            ['Azithromycine',         '250mg',    'AZI250',   '6.80',  '3.40'],
            ['Clamoxyl',              '500mg',    'CLA500',   '3.95',  '2.00'],
            ['Levothyrox',            '50µg',     'LEV50',    '2.33',  '1.15'],
            ['Levothyrox',            '100µg',    'LEV100',   '2.33',  '1.15'],
            ['Metformine',            '500mg',    'MET500',   '1.85',  '0.90'],
            ['Metformine',            '1000mg',   'MET1000',  '2.62',  '1.30'],
            ['Kardegic',              '75mg',     'KAR75',    '1.67',  '0.85'],
            ['Kardegic',              '160mg',    'KAR160',   '2.12',  '1.05'],
            ['Tahor',                 '10mg',     'TAH10',    '6.50',  '3.25'],
            ['Crestor',               '5mg',      'CRE5',     '8.35',  '4.20'],
            ['Ventoline',             '100µg',    'VEN100',   '3.75',  '1.85'],
            ['Seretide',              '250µg',    'SER250',   '28.30', '14.15'],
            ['Xanax',                 '0.25mg',   'XAN025',   '2.12',  '1.05'],
            ['Lexomil',               '6mg',      'LEX6',     '2.08',  '1.00'],
            ['Stilnox',               '10mg',     'STI10',    '2.30',  '1.15'],
            ['Deroxat',               '20mg',     'DER20',    '5.85',  '2.90'],
            ['Voltarene Emulgel',     '1%',       'VOL1P',    '6.20',  '3.10'],
            ['Voltarene',             '50mg',     'VOL50',    '3.45',  '1.70'],
            ['Flector Tissugel',      '180mg',    'FLE180',   '5.90',  '2.95'],
            ['Aerius',                '5mg',      'AER5',     '5.45',  '2.70'],
            ['Zyrtec',                '10mg',     'ZYR10',    '4.80',  '2.40'],
            ['Rhinofluimucil',        '2ml',      'RHI2ML',   '4.25',  '2.10'],
            ['Pivalone',              '1%',       'PIV1P',    '3.90',  '1.95'],
            ['Toplexil',              '0.33mg/ml','TOP033',   '3.50',  '1.75'],
            ['Maxilase',              '3000U',    'MAX3000',  '3.85',  '1.90'],
            ['Hexaspray',             '0.25%',    'HEX025',   '4.10',  '2.05'],
            ['Daflon',                '500mg',    'DAF500',   '6.90',  '3.45'],
            ['Forlax',                '10g',      'FOR10G',   '3.70',  '1.85'],
            ['Duphalac',              '10g/15ml', 'DUP10',    '2.90',  '1.45'],
            ['Oméprazole',            '20mg',     'OME20',    '2.50',  '1.25'],
            ['Inexium',               '40mg',     'INE40',    '9.60',  '4.80'],
            ['Débridat',              '100mg',    'DEB100',   '3.15',  '1.55'],
            ['Motilium',              '10mg',     'MOT10',    '2.75',  '1.35'],
            ['Lamaline',              '500mg',    'LAM500',   '2.85',  '1.40'],
            ['Codoliprane',           '500/30mg', 'COD530',   '2.15',  '1.05'],
            ['Biafine',               '1%',       'BIA1P',    '4.50',  '2.25'],
        ];

        // Distribution des stocks : on veut des cas variés
        // 10% rupture (0), 10% critique (1-5), 20% alerte (6-10), 60% normal (11-200)
        $stockDistribution = function () use ($faker): int {
            $rand = $faker->numberBetween(1, 100);
            if ($rand <= 10) {
                return 0; // Rupture de stock
            } elseif ($rand <= 20) {
                return $faker->numberBetween(1, 4); // Stock critique
            } elseif ($rand <= 40) {
                return $faker->numberBetween(5, 9); // Stock alerte
            } else {
                return $faker->numberBetween(15, 200); // Stock normal/abondant
            }
        };

        for ($i = 0; $i < 50; $i++) {
            $med = $medicaments[$i];

            $produit = new Produit();
            $produit->setNomProduit($med[0]);
            $produit->setDosageProduit($med[1]);
            $produit->setCodeProduit($med[2]);
            $produit->setPrixProduit($med[3]);
            $produit->setPrixAchat($med[4]);

            // Code CIP-13 : commence par 34009 (préfixe France) + 8 chiffres
            $produit->setCodeCip('34009' . $faker->numerify('########'));

            // Dates de fabrication (entre -2 ans et -3 mois) et expiration (+6 mois à +3 ans)
            $dateFab = $faker->dateTimeBetween('-2 years', '-3 months');
            $produit->setDateFabrication($dateFab);

            // Date d'expiration : 5% des produits expirent bientôt (dans 1-30 jours)
            if ($faker->numberBetween(1, 100) <= 5) {
                $produit->setDateExpiration(
                    $faker->dateTimeBetween('+1 days', '+30 days')
                );
            } else {
                $produit->setDateExpiration(
                    $faker->dateTimeBetween('+6 months', '+3 years')
                );
            }

            // Stocks variés
            $stock = $stockDistribution();
            $produit->setStockActuel($stock);
            $produit->setStockMinimum(5);
            $produit->setStockAlerte(10);

            $produit->setActif(true);

            $manager->persist($produit);
        }

        // =====================================================================
        // FLUSH FINAL
        // =====================================================================
        $manager->flush();
    }
}
