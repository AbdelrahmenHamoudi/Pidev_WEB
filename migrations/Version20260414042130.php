<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414042130 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table reservation_activite';
    }

    public function up(Schema $schema): void
    {
        // ── UNIQUEMENT la table de votre module ──
        $this->addSql('CREATE TABLE reservation_activite (
            id INT AUTO_INCREMENT NOT NULL,
            date_reservation DATETIME NOT NULL,
            statut VARCHAR(50) NOT NULL,
            nom_client VARCHAR(255) DEFAULT NULL,
            email_client VARCHAR(255) DEFAULT NULL,
            id_planning INT NOT NULL,
            id_utilisateur INT DEFAULT NULL,
            INDEX IDX_25C0B70184425363 (id_planning),
            INDEX IDX_25C0B70150EAE44 (id_utilisateur),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4');

        $this->addSql('ALTER TABLE reservation_activite ADD CONSTRAINT FK_25C0B70184425363 
            FOREIGN KEY (id_planning) REFERENCES planningactivite (id_planning)');

        $this->addSql('ALTER TABLE reservation_activite ADD CONSTRAINT FK_25C0B70150EAE44 
            FOREIGN KEY (id_utilisateur) REFERENCES users (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reservation_activite DROP FOREIGN KEY FK_25C0B70184425363');
        $this->addSql('ALTER TABLE reservation_activite DROP FOREIGN KEY FK_25C0B70150EAE44');
        $this->addSql('DROP TABLE reservation_activite');
    }
}