<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260921082329 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ligne_vente (Id_LigneVente INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, prix_unitaire_vente NUMERIC(10, 2) NOT NULL, Id_Vente INT NOT NULL, Id_Produit INT NOT NULL, INDEX IDX_8B26C07C5EB8262E (Id_Vente), INDEX IDX_8B26C07C77D87F1B (Id_Produit), PRIMARY KEY (Id_LigneVente)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vente (Id_Vente INT AUTO_INCREMENT NOT NULL, date_vente DATETIME NOT NULL, montant_total NUMERIC(10, 2) NOT NULL, Id_Patient INT DEFAULT NULL, Id_Ordonnance INT DEFAULT NULL, INDEX IDX_888A2A4C44A744D7 (Id_Patient), INDEX IDX_888A2A4CCBE2FA9E (Id_Ordonnance), PRIMARY KEY (Id_Vente)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ligne_vente ADD CONSTRAINT FK_8B26C07C5EB8262E FOREIGN KEY (Id_Vente) REFERENCES vente (Id_Vente)');
        $this->addSql('ALTER TABLE ligne_vente ADD CONSTRAINT FK_8B26C07C77D87F1B FOREIGN KEY (Id_Produit) REFERENCES produit (Id_Produit)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4C44A744D7 FOREIGN KEY (Id_Patient) REFERENCES patient (Id_Patient)');
        $this->addSql('ALTER TABLE vente ADD CONSTRAINT FK_888A2A4CCBE2FA9E FOREIGN KEY (Id_Ordonnance) REFERENCES ordonnance (Id_Ordonnance)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ligne_vente DROP FOREIGN KEY FK_8B26C07C5EB8262E');
        $this->addSql('ALTER TABLE ligne_vente DROP FOREIGN KEY FK_8B26C07C77D87F1B');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4C44A744D7');
        $this->addSql('ALTER TABLE vente DROP FOREIGN KEY FK_888A2A4CCBE2FA9E');
        $this->addSql('DROP TABLE ligne_vente');
        $this->addSql('DROP TABLE vente');
    }
}
