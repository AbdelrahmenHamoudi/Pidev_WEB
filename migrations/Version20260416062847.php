<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416062847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite ADD type VARCHAR(255) NOT NULL, ADD statut VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE planningactivite RENAME INDEX fk_1150e983e8aeb980 TO IDX_1150E983E8AEB980');
        $this->addSql('ALTER TABLE publication RENAME INDEX id_utilisateur TO IDX_AF3C677950EAE44');
        $this->addSql('ALTER TABLE reservation RENAME INDEX user_id TO IDX_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation RENAME INDEX hebergement_id TO IDX_42C8495523BB0F66');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY `trajet_ibfk_2`');
        $this->addSql('DROP INDEX id_utilisateur ON trajet');
        $this->addSql('ALTER TABLE trajet CHANGE id_utilisateur id_utilisateur INT NOT NULL, CHANGE distance_km distance_km DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE trajet RENAME INDEX id_voiture TO IDX_2B5BA98C377F287F');
        $this->addSql('ALTER TABLE user_2fa CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_2fa ADD CONSTRAINT FK_3AAA1488A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('CREATE INDEX IDX_3AAA1488A76ED395 ON user_2fa (user_id)');
        $this->addSql('ALTER TABLE voiture CHANGE prix_km prix_km DOUBLE PRECISION NOT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id BIGINT AUTO_INCREMENT NOT NULL, CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages (queue_name, available_at, delivered_at, id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activite DROP type, DROP statut');
        $this->addSql('DROP INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 ON messenger_messages');
        $this->addSql('ALTER TABLE messenger_messages CHANGE id id BIGINT NOT NULL, CHANGE delivered_at delivered_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE planningactivite RENAME INDEX idx_1150e983e8aeb980 TO FK_1150E983E8AEB980');
        $this->addSql('ALTER TABLE publication RENAME INDEX idx_af3c677950eae44 TO id_utilisateur');
        $this->addSql('ALTER TABLE reservation RENAME INDEX idx_42c8495523bb0f66 TO hebergement_id');
        $this->addSql('ALTER TABLE reservation RENAME INDEX idx_42c84955a76ed395 TO user_id');
        $this->addSql('ALTER TABLE trajet CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL, CHANGE distance_km distance_km VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT `trajet_ibfk_2` FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('CREATE INDEX id_utilisateur ON trajet (id_utilisateur)');
        $this->addSql('ALTER TABLE trajet RENAME INDEX idx_2b5ba98c377f287f TO id_voiture');
        $this->addSql('ALTER TABLE user_2fa DROP FOREIGN KEY FK_3AAA1488A76ED395');
        $this->addSql('DROP INDEX IDX_3AAA1488A76ED395 ON user_2fa');
        $this->addSql('ALTER TABLE user_2fa CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE voiture CHANGE prix_km prix_km VARCHAR(255) NOT NULL');
    }
}
