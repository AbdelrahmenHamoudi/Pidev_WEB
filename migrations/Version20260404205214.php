<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404205214 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE activite (id_activite INT AUTO_INCREMENT NOT NULL, nom_a VARCHAR(255) NOT NULL, description_a LONGTEXT NOT NULL, lieu VARCHAR(255) NOT NULL, prix_par_personne DOUBLE PRECISION NOT NULL, capacite_max INT NOT NULL, type VARCHAR(100) NOT NULL, statut VARCHAR(50) NOT NULL, image VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id_activite)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE planning (id_planning INT AUTO_INCREMENT NOT NULL, date_planning DATE NOT NULL, heure_debut TIME NOT NULL, heure_fin TIME NOT NULL, etat VARCHAR(50) NOT NULL, nb_places_restantes INT NOT NULL, id_activite INT NOT NULL, INDEX IDX_D499BFF6E8AEB980 (id_activite), PRIMARY KEY (id_planning)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE planning ADD CONSTRAINT FK_D499BFF6E8AEB980 FOREIGN KEY (id_activite) REFERENCES activite (id_activite)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE planning DROP FOREIGN KEY FK_D499BFF6E8AEB980');
        $this->addSql('DROP TABLE activite');
        $this->addSql('DROP TABLE planning');
    }
}
