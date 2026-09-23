<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260922093125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE medicament_bdpm (id INT AUTO_INCREMENT NOT NULL, code_cis VARCHAR(20) NOT NULL, denomination VARCHAR(500) NOT NULL, forme_pharmaceutique VARCHAR(255) DEFAULT NULL, voies_administration VARCHAR(255) DEFAULT NULL, statut_amm VARCHAR(100) DEFAULT NULL, titulaire VARCHAR(255) DEFAULT NULL, code_cip13 VARCHAR(13) DEFAULT NULL, libelle_presentation VARCHAR(500) DEFAULT NULL, prix_remboursement NUMERIC(10, 2) DEFAULT NULL, taux_remboursement VARCHAR(20) DEFAULT NULL, substance_active VARCHAR(500) DEFAULT NULL, dosage VARCHAR(255) DEFAULT NULL, derniere_maj DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_EBAC7C5420C36BA9 (code_cis), INDEX idx_denomination (denomination), INDEX idx_code_cis (code_cis), INDEX idx_code_cip13 (code_cip13), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE medicament_bdpm');
    }
}
