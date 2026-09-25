<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924161046 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit ADD emp_niveau VARCHAR(10) DEFAULT NULL, ADD emp_position VARCHAR(10) DEFAULT NULL, DROP numero_tiroir, CHANGE zone_stockage emp_zone VARCHAR(50) DEFAULT NULL, CHANGE colonne_tiroir emp_colonne VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE produit ADD colonne_tiroir VARCHAR(10) DEFAULT NULL, ADD numero_tiroir INT DEFAULT NULL, DROP emp_colonne, DROP emp_niveau, DROP emp_position, CHANGE emp_zone zone_stockage VARCHAR(50) DEFAULT NULL');
    }
}
