<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260328213524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE hebergement_image (id_image INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, ordre INT DEFAULT NULL, hebergement_id INT NOT NULL, INDEX IDX_4B499E4223BB0F66 (hebergement_id), PRIMARY KEY (id_image)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE hebergement_image ADD CONSTRAINT FK_4B499E4223BB0F66 FOREIGN KEY (hebergement_id) REFERENCES hebergement (id_hebergement)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hebergement_image DROP FOREIGN KEY FK_4B499E4223BB0F66');
        $this->addSql('DROP TABLE hebergement_image');
    }
}
