<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112131729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE medecin (Id_Medecin INT AUTO_INCREMENT NOT NULL, nom_medecin VARCHAR(255) NOT NULL, prenom_medecin VARCHAR(255) NOT NULL, contact_medecin VARCHAR(255) NOT NULL, adresse_medecin VARCHAR(255) NOT NULL, PRIMARY KEY (Id_Medecin)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE mutuelle (Id_Mutuelle INT AUTO_INCREMENT NOT NULL, nom_mutuelle VARCHAR(255) NOT NULL, contact_mutuelle VARCHAR(255) NOT NULL, taux_remboursement NUMERIC(10, 2) NOT NULL, PRIMARY KEY (Id_Mutuelle)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ordonnance (Id_Ordonnance INT AUTO_INCREMENT NOT NULL, date_ordonnance DATE NOT NULL, durée_traitement VARCHAR(255) NOT NULL, Id_Patient INT NOT NULL, Id_Medecin INT DEFAULT NULL, INDEX IDX_924B326C44A744D7 (Id_Patient), INDEX IDX_924B326C45A7C0FA (Id_Medecin), PRIMARY KEY (Id_Ordonnance)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Regroupe (Id_Ordonnance INT NOT NULL, Id_Produit INT NOT NULL, INDEX IDX_1ED46512CBE2FA9E (Id_Ordonnance), INDEX IDX_1ED4651277D87F1B (Id_Produit), PRIMARY KEY (Id_Ordonnance, Id_Produit)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE patient (Id_Patient INT AUTO_INCREMENT NOT NULL, nom_patient VARCHAR(255) NOT NULL, prenom_patient VARCHAR(255) NOT NULL, adresse_patient VARCHAR(255) NOT NULL, date_naissance DATE NOT NULL, PRIMARY KEY (Id_Patient)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE Posseder (Id_Patient INT NOT NULL, Id_Mutuelle INT NOT NULL, INDEX IDX_9B991EEC44A744D7 (Id_Patient), INDEX IDX_9B991EECA78930FA (Id_Mutuelle), PRIMARY KEY (Id_Patient, Id_Mutuelle)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE produit (Id_Produit INT AUTO_INCREMENT NOT NULL, code_produit VARCHAR(255) NOT NULL, date_fabrication DATE NOT NULL, date_expiration DATE NOT NULL, dosage_produit VARCHAR(255) NOT NULL, nom_produit VARCHAR(255) NOT NULL, prix_produit NUMERIC(10, 2) NOT NULL, stock_actuel INT NOT NULL, stock_minimum INT NOT NULL, stock_alerte INT NOT NULL, prix_achat NUMERIC(10, 2) DEFAULT NULL, code_cip VARCHAR(50) DEFAULT NULL, actif TINYINT(1) NOT NULL, PRIMARY KEY (Id_Produit)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_924B326C44A744D7 FOREIGN KEY (Id_Patient) REFERENCES patient (Id_Patient)');
        $this->addSql('ALTER TABLE ordonnance ADD CONSTRAINT FK_924B326C45A7C0FA FOREIGN KEY (Id_Medecin) REFERENCES medecin (Id_Medecin)');
        $this->addSql('ALTER TABLE Regroupe ADD CONSTRAINT FK_1ED46512CBE2FA9E FOREIGN KEY (Id_Ordonnance) REFERENCES ordonnance (Id_Ordonnance)');
        $this->addSql('ALTER TABLE Regroupe ADD CONSTRAINT FK_1ED4651277D87F1B FOREIGN KEY (Id_Produit) REFERENCES produit (Id_Produit)');
        $this->addSql('ALTER TABLE Posseder ADD CONSTRAINT FK_9B991EEC44A744D7 FOREIGN KEY (Id_Patient) REFERENCES patient (Id_Patient)');
        $this->addSql('ALTER TABLE Posseder ADD CONSTRAINT FK_9B991EECA78930FA FOREIGN KEY (Id_Mutuelle) REFERENCES mutuelle (Id_Mutuelle)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_924B326C44A744D7');
        $this->addSql('ALTER TABLE ordonnance DROP FOREIGN KEY FK_924B326C45A7C0FA');
        $this->addSql('ALTER TABLE Regroupe DROP FOREIGN KEY FK_1ED46512CBE2FA9E');
        $this->addSql('ALTER TABLE Regroupe DROP FOREIGN KEY FK_1ED4651277D87F1B');
        $this->addSql('ALTER TABLE Posseder DROP FOREIGN KEY FK_9B991EEC44A744D7');
        $this->addSql('ALTER TABLE Posseder DROP FOREIGN KEY FK_9B991EECA78930FA');
        $this->addSql('DROP TABLE medecin');
        $this->addSql('DROP TABLE mutuelle');
        $this->addSql('DROP TABLE ordonnance');
        $this->addSql('DROP TABLE Regroupe');
        $this->addSql('DROP TABLE patient');
        $this->addSql('DROP TABLE Posseder');
        $this->addSql('DROP TABLE produit');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
