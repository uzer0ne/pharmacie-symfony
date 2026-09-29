<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929080304 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande_fournisseur (id INT AUTO_INCREMENT NOT NULL, date_creation DATETIME NOT NULL, statut VARCHAR(20) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_commande_fournisseur (id INT AUTO_INCREMENT NOT NULL, quantite_commandee INT NOT NULL, prix_achat_unitaire NUMERIC(10, 2) DEFAULT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_9061513B82EA2E54 (commande_id), INDEX IDX_9061513BF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ligne_commande_fournisseur ADD CONSTRAINT FK_9061513B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande_fournisseur (id)');
        $this->addSql('ALTER TABLE ligne_commande_fournisseur ADD CONSTRAINT FK_9061513BF347EFB FOREIGN KEY (produit_id) REFERENCES produit (Id_Produit)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ligne_commande_fournisseur DROP FOREIGN KEY FK_9061513B82EA2E54');
        $this->addSql('ALTER TABLE ligne_commande_fournisseur DROP FOREIGN KEY FK_9061513BF347EFB');
        $this->addSql('DROP TABLE commande_fournisseur');
        $this->addSql('DROP TABLE ligne_commande_fournisseur');
    }
}
