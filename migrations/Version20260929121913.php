<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929121913 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE absence (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, motif VARCHAR(50) NOT NULL, valide TINYINT(1) NOT NULL, user_id INT NOT NULL, INDEX IDX_765AE0C9A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE creneau_planning (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, type_creneau VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, user_id INT NOT NULL, INDEX IDX_8318DCB3A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE horaire_ouverture (id INT AUTO_INCREMENT NOT NULL, jour_semaine SMALLINT NOT NULL, heure_debut_matin TIME DEFAULT NULL, heure_fin_matin TIME DEFAULT NULL, heure_debut_aprem TIME DEFAULT NULL, heure_fin_aprem TIME DEFAULT NULL, est_ouvert TINYINT(1) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT FK_765AE0C9A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE creneau_planning ADD CONSTRAINT FK_8318DCB3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user ADD qualification VARCHAR(50) DEFAULT NULL, ADD temps_travail_hebdo DOUBLE PRECISION DEFAULT NULL, ADD numero_rpps VARCHAR(20) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY FK_765AE0C9A76ED395');
        $this->addSql('ALTER TABLE creneau_planning DROP FOREIGN KEY FK_8318DCB3A76ED395');
        $this->addSql('DROP TABLE absence');
        $this->addSql('DROP TABLE creneau_planning');
        $this->addSql('DROP TABLE horaire_ouverture');
        $this->addSql('ALTER TABLE user DROP qualification, DROP temps_travail_hebdo, DROP numero_rpps');
    }
}
