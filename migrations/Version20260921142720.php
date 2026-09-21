<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260921142720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ligne_ordonnance ADD quantite INT NOT NULL, ADD produit_id INT NOT NULL, DROP nom_medicament');
        $this->addSql('ALTER TABLE ligne_ordonnance ADD CONSTRAINT FK_71E7DC71F347EFB FOREIGN KEY (produit_id) REFERENCES produit (Id_Produit)');
        $this->addSql('CREATE INDEX IDX_71E7DC71F347EFB ON ligne_ordonnance (produit_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ligne_ordonnance DROP FOREIGN KEY FK_71E7DC71F347EFB');
        $this->addSql('DROP INDEX IDX_71E7DC71F347EFB ON ligne_ordonnance');
        $this->addSql('ALTER TABLE ligne_ordonnance ADD nom_medicament VARCHAR(255) NOT NULL, DROP quantite, DROP produit_id');
    }
}
