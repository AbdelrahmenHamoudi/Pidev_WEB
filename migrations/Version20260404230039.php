<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Cleaned up to avoid dropping untracked entity tables.
 */
final class Version20260404230039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ville, pays to hebergement and ordre to hebergement_image';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hebergement ADD ville VARCHAR(255) DEFAULT NULL, ADD pays VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE hebergement_image ADD ordre INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hebergement DROP ville, DROP pays');
        $this->addSql('ALTER TABLE hebergement_image DROP ordre');
    }
}
