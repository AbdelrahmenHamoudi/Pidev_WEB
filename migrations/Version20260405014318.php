<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405014318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE activite ADD nom_a VARCHAR(255) NOT NULL, ADD description_a LONGTEXT NOT NULL, ADD prix_par_personne VARCHAR(255) NOT NULL, ADD capacite_max INT NOT NULL, DROP nomA, DROP descriptionA, DROP prixParPersonne, DROP capaciteMax, CHANGE idActivite idActivite INT NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL, CHANGE image image VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX idx_created_at ON admin_action_logs');
        $this->addSql('DROP INDEX idx_action_type ON admin_action_logs');
        $this->addSql('ALTER TABLE admin_action_logs DROP FOREIGN KEY admin_action_logs_ibfk_1');
        $this->addSql('ALTER TABLE admin_action_logs CHANGE id id INT NOT NULL, CHANGE admin_id admin_id INT DEFAULT NULL, CHANGE action_type action_type VARCHAR(255) NOT NULL, CHANGE target_type target_type VARCHAR(50) NOT NULL, CHANGE target_id target_id INT NOT NULL, CHANGE target_description target_description VARCHAR(255) NOT NULL, CHANGE details details LONGTEXT NOT NULL, CHANGE ip_address ip_address VARCHAR(45) NOT NULL');
        $this->addSql('DROP INDEX idx_admin_id ON admin_action_logs');
        $this->addSql('CREATE INDEX IDX_1B74345B642B8210 ON admin_action_logs (admin_id)');
        $this->addSql('ALTER TABLE admin_action_logs ADD CONSTRAINT admin_action_logs_ibfk_1 FOREIGN KEY (admin_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE admin_notifications CHANGE id id INT NOT NULL, CHANGE message message LONGTEXT NOT NULL, CHANGE type type VARCHAR(255) NOT NULL, CHANGE priority priority VARCHAR(255) NOT NULL, CHANGE is_read is_read TINYINT(1) NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE action_link action_link VARCHAR(255) NOT NULL, CHANGE action_text action_text VARCHAR(50) NOT NULL, CHANGE created_by created_by VARCHAR(100) NOT NULL');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_publication');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_user');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_publication');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_user');
        $this->addSql('ALTER TABLE commentaire CHANGE idCommentaire idCommentaire INT NOT NULL, CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL, CHANGE idPublication idPublication INT DEFAULT NULL, CHANGE contenuC contenu_c VARCHAR(200) NOT NULL, CHANGE dateCreationC date_creation_c VARCHAR(50) NOT NULL, CHANGE statutC statut_c TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCEF619801 FOREIGN KEY (idPublication) REFERENCES publication (idPublication) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_commentaire_user ON commentaire');
        $this->addSql('CREATE INDEX IDX_67F068BC50EAE44 ON commentaire (id_utilisateur)');
        $this->addSql('DROP INDEX fk_commentaire_publication ON commentaire');
        $this->addSql('CREATE INDEX IDX_67F068BCEF619801 ON commentaire (idPublication)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT fk_commentaire_publication FOREIGN KEY (idPublication) REFERENCES publication (idPublication) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT fk_commentaire_user FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_login_time ON connexion_logs');
        $this->addSql('ALTER TABLE connexion_logs DROP FOREIGN KEY connexion_logs_ibfk_1');
        $this->addSql('ALTER TABLE connexion_logs CHANGE id id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE logout_time logout_time DATETIME NOT NULL, CHANGE ip_address ip_address VARCHAR(45) NOT NULL, CHANGE device_info device_info VARCHAR(255) NOT NULL, CHANGE success success TINYINT(1) NOT NULL, CHANGE failure_reason failure_reason VARCHAR(255) NOT NULL, CHANGE country country VARCHAR(100) NOT NULL, CHANGE city city VARCHAR(100) NOT NULL, CHANGE latitude latitude DOUBLE PRECISION NOT NULL, CHANGE longitude longitude DOUBLE PRECISION NOT NULL');
        $this->addSql('DROP INDEX idx_user_id ON connexion_logs');
        $this->addSql('CREATE INDEX IDX_5CDCD863A76ED395 ON connexion_logs (user_id)');
        $this->addSql('ALTER TABLE connexion_logs ADD CONSTRAINT connexion_logs_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_verification_tokens DROP FOREIGN KEY email_verification_tokens_ibfk_1');
        $this->addSql('ALTER TABLE email_verification_tokens CHANGE id id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE expires_at expires_at DATETIME NOT NULL, CHANGE verified verified TINYINT(1) NOT NULL');
        $this->addSql('DROP INDEX user_id ON email_verification_tokens');
        $this->addSql('CREATE INDEX IDX_C81CA2ACA76ED395 ON email_verification_tokens (user_id)');
        $this->addSql('ALTER TABLE email_verification_tokens ADD CONSTRAINT email_verification_tokens_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hebergement ADD prix_par_nuit VARCHAR(255) NOT NULL, DROP prixParNuit, CHANGE id_hebergement id_hebergement INT NOT NULL, CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE desc_hebergement desc_hebergement VARCHAR(255) NOT NULL, CHANGE capacite capacite INT NOT NULL, CHANGE type_hebergement type_hebergement VARCHAR(100) NOT NULL, CHANGE disponible_heberg disponible_heberg TINYINT(1) NOT NULL, CHANGE image image VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY notification_ibfk_1');
        $this->addSql('ALTER TABLE notification CHANGE id id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE message message LONGTEXT NOT NULL, CHANGE lue lue TINYINT(1) NOT NULL, CHANGE date_creation date_creation DATETIME NOT NULL');
        $this->addSql('DROP INDEX user_id ON notification');
        $this->addSql('CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT notification_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planningactivite DROP FOREIGN KEY fk_planning_activite');
        $this->addSql('ALTER TABLE planningactivite DROP FOREIGN KEY fk_planning_user');
        $this->addSql('ALTER TABLE planningactivite DROP FOREIGN KEY fk_planning_activite');
        $this->addSql('ALTER TABLE planningactivite DROP FOREIGN KEY fk_planning_user');
        $this->addSql('ALTER TABLE planningactivite ADD id_planning INT NOT NULL, ADD heure_debut VARCHAR(255) NOT NULL, ADD heure_fin VARCHAR(255) NOT NULL, ADD date_planning DATE NOT NULL, ADD nb_places_restantes INT NOT NULL, DROP idPlanning, DROP heureDebut, DROP heureFin, DROP datePlanning, DROP nbPlacesRestantes, CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL, CHANGE id_activite id_activite INT DEFAULT NULL, CHANGE etat etat VARCHAR(50) NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id_planning)');
        $this->addSql('ALTER TABLE planningactivite ADD CONSTRAINT FK_1150E98350EAE44 FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planningactivite ADD CONSTRAINT FK_1150E983E8AEB980 FOREIGN KEY (id_activite) REFERENCES activite (idActivite) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_planning_user ON planningactivite');
        $this->addSql('CREATE INDEX IDX_1150E98350EAE44 ON planningactivite (id_utilisateur)');
        $this->addSql('DROP INDEX fk_planning_activite ON planningactivite');
        $this->addSql('CREATE INDEX IDX_1150E983E8AEB980 ON planningactivite (id_activite)');
        $this->addSql('ALTER TABLE planningactivite ADD CONSTRAINT fk_planning_activite FOREIGN KEY (id_activite) REFERENCES activite (idActivite) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planningactivite ADD CONSTRAINT fk_planning_user FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY fk_publication_user');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY fk_publication_commentaire');
        $this->addSql('DROP INDEX idx_pub_commentaire ON publication');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY fk_publication_user');
        $this->addSql('ALTER TABLE publication ADD type_cible VARCHAR(255) NOT NULL, ADD date_creation VARCHAR(50) NOT NULL, ADD date_modif VARCHAR(50) NOT NULL, ADD statut_p VARCHAR(255) NOT NULL, DROP idCommentaire, DROP typeCible, DROP dateCreation, DROP dateModif, DROP statutP, CHANGE idPublication idPublication INT NOT NULL, CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL, CHANGE ImgURL img_url VARCHAR(100) NOT NULL, CHANGE estVerifie est_verifie TINYINT(1) NOT NULL, CHANGE DescriptionP description_p VARCHAR(200) NOT NULL');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677950EAE44 FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_pub_user ON publication');
        $this->addSql('CREATE INDEX IDX_AF3C677950EAE44 ON publication (id_utilisateur)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT fk_publication_user FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_reservation_hebergement');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_reservation_user');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_reservation_hebergement');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY fk_reservation_user');
        $this->addSql('ALTER TABLE reservation ADD date_debut_r DATE NOT NULL, ADD date_fin_r DATE NOT NULL, ADD statut_r VARCHAR(50) NOT NULL, DROP dateDebutR, DROP dateFinR, DROP statutR, CHANGE id_reservation id_reservation INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE hebergement_id hebergement_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495523BB0F66 FOREIGN KEY (hebergement_id) REFERENCES hebergement (id_hebergement) ON DELETE CASCADE');
        $this->addSql('DROP INDEX fk_reservation_user ON reservation');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql('DROP INDEX fk_reservation_hebergement ON reservation');
        $this->addSql('CREATE INDEX IDX_42C8495523BB0F66 ON reservation (hebergement_id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_reservation_hebergement FOREIGN KEY (hebergement_id) REFERENCES hebergement (id_hebergement) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_reservation_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY trajet_ibfk_1');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY trajet_ibfk_2');
        $this->addSql('ALTER TABLE trajet CHANGE id_trajet id_trajet INT NOT NULL, CHANGE id_voiture id_voiture INT DEFAULT NULL, CHANGE id_utilisateur id_utilisateur INT DEFAULT NULL, CHANGE point_depart point_depart VARCHAR(255) NOT NULL, CHANGE point_arrivee point_arrivee VARCHAR(255) NOT NULL, CHANGE distance_km distance_km VARCHAR(255) NOT NULL, CHANGE date_reservation date_reservation DATE NOT NULL, CHANGE statut statut VARCHAR(50) NOT NULL');
        $this->addSql('DROP INDEX id_voiture ON trajet');
        $this->addSql('CREATE INDEX IDX_2B5BA98C377F287F ON trajet (id_voiture)');
        $this->addSql('DROP INDEX id_utilisateur ON trajet');
        $this->addSql('CREATE INDEX IDX_2B5BA98C50EAE44 ON trajet (id_utilisateur)');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT trajet_ibfk_1 FOREIGN KEY (id_voiture) REFERENCES voiture (id_voiture) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT trajet_ibfk_2 FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_2fa DROP FOREIGN KEY user_2fa_ibfk_1');
        $this->addSql('ALTER TABLE user_2fa CHANGE id id INT NOT NULL, CHANGE user_id user_id INT DEFAULT NULL, CHANGE enabled enabled TINYINT(1) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE backup_codes backup_codes LONGTEXT NOT NULL');
        $this->addSql('DROP INDEX user_id ON user_2fa');
        $this->addSql('CREATE INDEX IDX_3AAA1488A76ED395 ON user_2fa (user_id)');
        $this->addSql('ALTER TABLE user_2fa ADD CONSTRAINT user_2fa_ibfk_1 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users CHANGE id id INT NOT NULL, CHANGE nom nom VARCHAR(255) NOT NULL, CHANGE prenom prenom VARCHAR(255) NOT NULL, CHANGE date_naiss date_naiss VARCHAR(255) NOT NULL, CHANGE e_mail e_mail VARCHAR(255) NOT NULL, CHANGE num_tel num_tel VARCHAR(20) NOT NULL, CHANGE mot_de_pass mot_de_pass VARCHAR(255) NOT NULL, CHANGE image image VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL, CHANGE status status VARCHAR(255) NOT NULL, CHANGE email_verified email_verified TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE voiture CHANGE id_voiture id_voiture INT NOT NULL, CHANGE marque marque VARCHAR(255) NOT NULL, CHANGE modele modele VARCHAR(255) NOT NULL, CHANGE immatriculation immatriculation VARCHAR(50) NOT NULL, CHANGE prix_KM prix_km VARCHAR(255) NOT NULL, CHANGE avec_chauffeur avec_chauffeur TINYINT(1) NOT NULL, CHANGE disponibilite disponibilite TINYINT(1) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE image image VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE activite ADD nomA VARCHAR(255) DEFAULT NULL, ADD descriptionA TEXT DEFAULT NULL, ADD prixParPersonne FLOAT DEFAULT NULL, ADD capaciteMax INT DEFAULT NULL, DROP nom_a, DROP description_a, DROP prix_par_personne, DROP capacite_max, CHANGE idActivite idActivite INT AUTO_INCREMENT NOT NULL, CHANGE lieu lieu VARCHAR(255) DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE admin_action_logs DROP FOREIGN KEY FK_1B74345B642B8210');
        $this->addSql('ALTER TABLE admin_action_logs CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE action_type action_type ENUM(\'CREATE\', \'UPDATE\', \'DELETE\', \'LOGIN\', \'LOGOUT\', \'SUSPEND\', \'ACTIVATE\', \'EXPORT\', \'VIEW\') NOT NULL, CHANGE target_type target_type VARCHAR(50) DEFAULT NULL, CHANGE target_id target_id INT DEFAULT NULL, CHANGE target_description target_description VARCHAR(255) DEFAULT NULL, CHANGE details details TEXT DEFAULT NULL, CHANGE ip_address ip_address VARCHAR(45) DEFAULT NULL, CHANGE admin_id admin_id INT NOT NULL');
        $this->addSql('CREATE INDEX idx_created_at ON admin_action_logs (created_at)');
        $this->addSql('CREATE INDEX idx_action_type ON admin_action_logs (action_type)');
        $this->addSql('DROP INDEX idx_1b74345b642b8210 ON admin_action_logs');
        $this->addSql('CREATE INDEX idx_admin_id ON admin_action_logs (admin_id)');
        $this->addSql('ALTER TABLE admin_action_logs ADD CONSTRAINT FK_1B74345B642B8210 FOREIGN KEY (admin_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE admin_notifications CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE message message TEXT NOT NULL, CHANGE type type ENUM(\'INFO\', \'SUCCESS\', \'WARNING\', \'ERROR\') DEFAULT \'INFO\', CHANGE priority priority ENUM(\'LOW\', \'MEDIUM\', \'HIGH\', \'URGENT\') DEFAULT \'MEDIUM\', CHANGE is_read is_read TINYINT(1) DEFAULT 0, CHANGE expires_at expires_at DATETIME DEFAULT NULL, CHANGE action_link action_link VARCHAR(255) DEFAULT NULL, CHANGE action_text action_text VARCHAR(50) DEFAULT NULL, CHANGE created_by created_by VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC50EAE44');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCEF619801');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BC50EAE44');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCEF619801');
        $this->addSql('ALTER TABLE commentaire CHANGE idCommentaire idCommentaire INT AUTO_INCREMENT NOT NULL, CHANGE id_utilisateur id_utilisateur INT NOT NULL, CHANGE idPublication idPublication INT NOT NULL, CHANGE contenu_c contenuC VARCHAR(200) NOT NULL, CHANGE date_creation_c dateCreationC VARCHAR(50) NOT NULL, CHANGE statut_c statutC TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT fk_commentaire_publication FOREIGN KEY (idPublication) REFERENCES publication (idPublication) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT fk_commentaire_user FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_67f068bc50eae44 ON commentaire');
        $this->addSql('CREATE INDEX fk_commentaire_user ON commentaire (id_utilisateur)');
        $this->addSql('DROP INDEX idx_67f068bcef619801 ON commentaire');
        $this->addSql('CREATE INDEX fk_commentaire_publication ON commentaire (idPublication)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BC50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCEF619801 FOREIGN KEY (idPublication) REFERENCES publication (idPublication) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE connexion_logs DROP FOREIGN KEY FK_5CDCD863A76ED395');
        $this->addSql('ALTER TABLE connexion_logs CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE logout_time logout_time DATETIME DEFAULT NULL, CHANGE ip_address ip_address VARCHAR(45) DEFAULT NULL, CHANGE device_info device_info VARCHAR(255) DEFAULT NULL, CHANGE success success TINYINT(1) DEFAULT 1, CHANGE failure_reason failure_reason VARCHAR(255) DEFAULT NULL, CHANGE country country VARCHAR(100) DEFAULT NULL, CHANGE city city VARCHAR(100) DEFAULT NULL, CHANGE latitude latitude DOUBLE PRECISION DEFAULT NULL, CHANGE longitude longitude DOUBLE PRECISION DEFAULT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('CREATE INDEX idx_login_time ON connexion_logs (login_time)');
        $this->addSql('DROP INDEX idx_5cdcd863a76ed395 ON connexion_logs');
        $this->addSql('CREATE INDEX idx_user_id ON connexion_logs (user_id)');
        $this->addSql('ALTER TABLE connexion_logs ADD CONSTRAINT FK_5CDCD863A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE email_verification_tokens DROP FOREIGN KEY FK_C81CA2ACA76ED395');
        $this->addSql('ALTER TABLE email_verification_tokens CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE expires_at expires_at DATETIME DEFAULT NULL, CHANGE verified verified TINYINT(1) DEFAULT 0, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('DROP INDEX idx_c81ca2aca76ed395 ON email_verification_tokens');
        $this->addSql('CREATE INDEX user_id ON email_verification_tokens (user_id)');
        $this->addSql('ALTER TABLE email_verification_tokens ADD CONSTRAINT FK_C81CA2ACA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE hebergement ADD prixParNuit FLOAT DEFAULT NULL, DROP prix_par_nuit, CHANGE id_hebergement id_hebergement INT AUTO_INCREMENT NOT NULL, CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE desc_hebergement desc_hebergement VARCHAR(255) DEFAULT NULL, CHANGE capacite capacite INT DEFAULT NULL, CHANGE type_hebergement type_hebergement VARCHAR(100) DEFAULT NULL, CHANGE disponible_heberg disponible_heberg TINYINT(1) DEFAULT 1, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE notification CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE message message TEXT NOT NULL, CHANGE lue lue TINYINT(1) DEFAULT 0, CHANGE date_creation date_creation DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('DROP INDEX idx_bf5476caa76ed395 ON notification');
        $this->addSql('CREATE INDEX user_id ON notification (user_id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planningactivite DROP FOREIGN KEY FK_1150E98350EAE44');
        $this->addSql('ALTER TABLE planningactivite DROP FOREIGN KEY FK_1150E983E8AEB980');
        $this->addSql('ALTER TABLE planningactivite DROP FOREIGN KEY FK_1150E98350EAE44');
        $this->addSql('ALTER TABLE planningactivite DROP FOREIGN KEY FK_1150E983E8AEB980');
        $this->addSql('ALTER TABLE planningactivite ADD idPlanning INT AUTO_INCREMENT NOT NULL, ADD heureDebut TIME DEFAULT NULL, ADD heureFin TIME DEFAULT NULL, ADD datePlanning DATE DEFAULT NULL, ADD nbPlacesRestantes INT DEFAULT NULL, DROP id_planning, DROP heure_debut, DROP heure_fin, DROP date_planning, DROP nb_places_restantes, CHANGE etat etat VARCHAR(50) DEFAULT NULL, CHANGE id_utilisateur id_utilisateur INT NOT NULL, CHANGE id_activite id_activite INT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (idPlanning)');
        $this->addSql('ALTER TABLE planningactivite ADD CONSTRAINT fk_planning_activite FOREIGN KEY (id_activite) REFERENCES activite (idActivite) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planningactivite ADD CONSTRAINT fk_planning_user FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_1150e983e8aeb980 ON planningactivite');
        $this->addSql('CREATE INDEX fk_planning_activite ON planningactivite (id_activite)');
        $this->addSql('DROP INDEX idx_1150e98350eae44 ON planningactivite');
        $this->addSql('CREATE INDEX fk_planning_user ON planningactivite (id_utilisateur)');
        $this->addSql('ALTER TABLE planningactivite ADD CONSTRAINT FK_1150E98350EAE44 FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE planningactivite ADD CONSTRAINT FK_1150E983E8AEB980 FOREIGN KEY (id_activite) REFERENCES activite (idActivite) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C677950EAE44');
        $this->addSql('ALTER TABLE publication DROP FOREIGN KEY FK_AF3C677950EAE44');
        $this->addSql('ALTER TABLE publication ADD idCommentaire INT DEFAULT NULL, ADD typeCible ENUM(\'HEBERGEMENT\', \'ACTIVITE\', \'TRANSPORT\') NOT NULL, ADD dateCreation VARCHAR(50) NOT NULL, ADD dateModif VARCHAR(50) NOT NULL, ADD statutP ENUM(\'EN_ATTENTE\', \'VALIDE\', \'MASQUE\') NOT NULL, DROP type_cible, DROP date_creation, DROP date_modif, DROP statut_p, CHANGE idPublication idPublication INT AUTO_INCREMENT NOT NULL, CHANGE id_utilisateur id_utilisateur INT NOT NULL, CHANGE img_url ImgURL VARCHAR(100) NOT NULL, CHANGE est_verifie estVerifie TINYINT(1) NOT NULL, CHANGE description_p DescriptionP VARCHAR(200) NOT NULL');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT fk_publication_user FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT fk_publication_commentaire FOREIGN KEY (idCommentaire) REFERENCES commentaire (idCommentaire) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->addSql('CREATE INDEX idx_pub_commentaire ON publication (idCommentaire)');
        $this->addSql('DROP INDEX idx_af3c677950eae44 ON publication');
        $this->addSql('CREATE INDEX idx_pub_user ON publication (id_utilisateur)');
        $this->addSql('ALTER TABLE publication ADD CONSTRAINT FK_AF3C677950EAE44 FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495523BB0F66');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C8495523BB0F66');
        $this->addSql('ALTER TABLE reservation ADD dateDebutR DATE DEFAULT NULL, ADD dateFinR DATE DEFAULT NULL, ADD statutR VARCHAR(50) DEFAULT NULL, DROP date_debut_r, DROP date_fin_r, DROP statut_r, CHANGE id_reservation id_reservation INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE hebergement_id hebergement_id INT NOT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_reservation_hebergement FOREIGN KEY (hebergement_id) REFERENCES hebergement (id_hebergement) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_reservation_user FOREIGN KEY (user_id) REFERENCES users (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_42c8495523bb0f66 ON reservation');
        $this->addSql('CREATE INDEX fk_reservation_hebergement ON reservation (hebergement_id)');
        $this->addSql('DROP INDEX idx_42c84955a76ed395 ON reservation');
        $this->addSql('CREATE INDEX fk_reservation_user ON reservation (user_id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C8495523BB0F66 FOREIGN KEY (hebergement_id) REFERENCES hebergement (id_hebergement) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY FK_2B5BA98C377F287F');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY FK_2B5BA98C50EAE44');
        $this->addSql('ALTER TABLE trajet CHANGE id_trajet id_trajet INT AUTO_INCREMENT NOT NULL, CHANGE point_depart point_depart VARCHAR(255) DEFAULT NULL, CHANGE point_arrivee point_arrivee VARCHAR(255) DEFAULT NULL, CHANGE distance_km distance_km FLOAT DEFAULT NULL, CHANGE date_reservation date_reservation DATE DEFAULT NULL, CHANGE statut statut VARCHAR(50) DEFAULT NULL, CHANGE id_voiture id_voiture INT NOT NULL, CHANGE id_utilisateur id_utilisateur INT NOT NULL');
        $this->addSql('DROP INDEX idx_2b5ba98c377f287f ON trajet');
        $this->addSql('CREATE INDEX id_voiture ON trajet (id_voiture)');
        $this->addSql('DROP INDEX idx_2b5ba98c50eae44 ON trajet');
        $this->addSql('CREATE INDEX id_utilisateur ON trajet (id_utilisateur)');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT FK_2B5BA98C377F287F FOREIGN KEY (id_voiture) REFERENCES voiture (id_voiture) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT FK_2B5BA98C50EAE44 FOREIGN KEY (id_utilisateur) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE users CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE nom nom VARCHAR(255) DEFAULT NULL, CHANGE prenom prenom VARCHAR(255) DEFAULT NULL, CHANGE date_naiss date_naiss VARCHAR(255) DEFAULT NULL, CHANGE e_mail e_mail VARCHAR(255) DEFAULT NULL, CHANGE num_tel num_tel VARCHAR(20) DEFAULT NULL, CHANGE mot_de_pass mot_de_pass VARCHAR(255) DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE role role ENUM(\'admin\', \'user\') DEFAULT \'user\', CHANGE status status ENUM(\'Banned\', \'Unbanned\') DEFAULT \'Unbanned\', CHANGE email_verified email_verified TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE user_2fa DROP FOREIGN KEY FK_3AAA1488A76ED395');
        $this->addSql('ALTER TABLE user_2fa CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE enabled enabled TINYINT(1) DEFAULT 0, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE backup_codes backup_codes TEXT DEFAULT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('DROP INDEX idx_3aaa1488a76ed395 ON user_2fa');
        $this->addSql('CREATE INDEX user_id ON user_2fa (user_id)');
        $this->addSql('ALTER TABLE user_2fa ADD CONSTRAINT FK_3AAA1488A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE voiture CHANGE id_voiture id_voiture INT AUTO_INCREMENT NOT NULL, CHANGE marque marque VARCHAR(255) DEFAULT NULL, CHANGE modele modele VARCHAR(255) DEFAULT NULL, CHANGE immatriculation immatriculation VARCHAR(50) DEFAULT NULL, CHANGE prix_km prix_KM FLOAT DEFAULT NULL, CHANGE avec_chauffeur avec_chauffeur TINYINT(1) DEFAULT NULL, CHANGE disponibilite disponibilite TINYINT(1) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
    }
}
