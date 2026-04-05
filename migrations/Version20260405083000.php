<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add images JSON column to hebergement and drop hebergement_image table
 */
final class Version20260405083000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add images JSON column to hebergement and drop hebergement_image table';
    }

    public function up(Schema $schema): void
    {
        // Add images column as JSON
        $this->addSql('ALTER TABLE hebergement ADD images JSON DEFAULT NULL');

        // Initialize with empty array for existing records
        $this->addSql('UPDATE hebergement SET images = NULL');

        // Drop the hebergement_image table
        $this->addSql('DROP TABLE IF EXISTS hebergement_image');
    }

    public function down(Schema $schema): void
    {
        // Recreate hebergement_image table
        $this->addSql('
            CREATE TABLE hebergement_image (
                id_image INT AUTO_INCREMENT NOT NULL,
                hebergement_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                ordre INT DEFAULT NULL,
                PRIMARY KEY (id_image),
                INDEX IDX_HEBERGEMENT_ID (hebergement_id),
                CONSTRAINT FK_HEBERGEMENT_IMAGE_HEBERGEMENT
                    FOREIGN KEY (hebergement_id)
                    REFERENCES hebergement (id_hebergement)
                    ON DELETE CASCADE
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');

        // Drop images column
        $this->addSql('ALTER TABLE hebergement DROP images');
    }
}
