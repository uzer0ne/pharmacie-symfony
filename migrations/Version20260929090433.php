<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929090433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE fournisseur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commande_fournisseur ADD fournisseur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE commande_fournisseur ADD CONSTRAINT FK_7F6F4F53670C757F FOREIGN KEY (fournisseur_id) REFERENCES fournisseur (id)');
        $this->addSql('CREATE INDEX IDX_7F6F4F53670C757F ON commande_fournisseur (fournisseur_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE fournisseur');
        $this->addSql('ALTER TABLE commande_fournisseur DROP FOREIGN KEY FK_7F6F4F53670C757F');
        $this->addSql('DROP INDEX IDX_7F6F4F53670C757F ON commande_fournisseur');
        $this->addSql('ALTER TABLE commande_fournisseur DROP fournisseur_id');
    }
}
