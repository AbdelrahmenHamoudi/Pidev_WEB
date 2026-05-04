<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Make hebergement_id nullable in reservation table with SET NULL on delete
 */
final class Version20260405084000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make hebergement_id nullable in reservation table with SET NULL on delete';
    }

    public function up(Schema $schema): void
    {
        // Modify column to be nullable
        $this->addSql('ALTER TABLE reservation MODIFY hebergement_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert to NOT NULL
        $this->addSql('ALTER TABLE reservation MODIFY hebergement_id INT NOT NULL');
    }
}
