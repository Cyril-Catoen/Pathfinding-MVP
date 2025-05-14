<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514220449 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE adventure (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, title VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, status VARCHAR(255) NOT NULL, map_gpx VARCHAR(255) DEFAULT NULL, view_authorization VARCHAR(255) NOT NULL, share_link VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_9E858E0F8B6B9468 (share_link), INDEX IDX_9E858E0F7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE adventure_adventure_type (adventure_id INT NOT NULL, adventure_type_id INT NOT NULL, INDEX IDX_AEA3EC6255CF40F9 (adventure_id), INDEX IDX_AEA3EC629046F2F7 (adventure_type_id), PRIMARY KEY(adventure_id, adventure_type_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE adventure_user (adventure_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_575C8E7455CF40F9 (adventure_id), INDEX IDX_575C8E74A76ED395 (user_id), PRIMARY KEY(adventure_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE adventure_file (id INT AUTO_INCREMENT NOT NULL, adventure_id INT NOT NULL, file_name VARCHAR(255) NOT NULL, external_url VARCHAR(255) DEFAULT NULL, is_external TINYINT(1) NOT NULL, type VARCHAR(255) NOT NULL, view_authorization VARCHAR(255) NOT NULL, uploaded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', size INT NOT NULL, mime_type VARCHAR(100) DEFAULT NULL, file_extension VARCHAR(10) DEFAULT NULL, INDEX IDX_56506E2D55CF40F9 (adventure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE adventure_point (id INT AUTO_INCREMENT NOT NULL, adventure_id INT NOT NULL, latitude DOUBLE PRECISION NOT NULL, longitude DOUBLE PRECISION NOT NULL, elevation DOUBLE PRECISION DEFAULT NULL, recorded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_EF17706D55CF40F9 (adventure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE adventure_type (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_56110F145E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE safety_alert (id INT AUTO_INCREMENT NOT NULL, adventure_id INT NOT NULL, triggered_by_id INT DEFAULT NULL, triggered_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', status VARCHAR(255) NOT NULL, message LONGTEXT DEFAULT NULL, delivery_method JSON NOT NULL, reason VARCHAR(255) NOT NULL, acknowledged_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_EBB6D2355CF40F9 (adventure_id), INDEX IDX_EBB6D2363C5923F (triggered_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE safety_alert_safety_contact (safety_alert_id INT NOT NULL, safety_contact_id INT NOT NULL, INDEX IDX_5B9BC5CFD0AA4269 (safety_alert_id), INDEX IDX_5B9BC5CF2D91C1A0 (safety_contact_id), PRIMARY KEY(safety_alert_id, safety_contact_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE safety_contact (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, email VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, phone_number VARCHAR(20) NOT NULL, country VARCHAR(100) NOT NULL, declaration_of_majority TINYINT(1) NOT NULL, is_verified TINYINT(1) NOT NULL, verification_token VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX UNIQ_8C969750E7927C74 (email), INDEX IDX_8C969750A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE timer_alert (id INT AUTO_INCREMENT NOT NULL, updated_by_user_id INT DEFAULT NULL, adventure_id INT NOT NULL, alert_time DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_active TINYINT(1) NOT NULL, updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_6A59BDF72793CC5E (updated_by_user_id), UNIQUE INDEX UNIQ_6A59BDF755CF40F9 (adventure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, surname VARCHAR(255) NOT NULL, birthdate DATE NOT NULL, path VARCHAR(255) DEFAULT NULL, path_number VARCHAR(255) DEFAULT NULL, postcode VARCHAR(10) DEFAULT NULL, city VARCHAR(100) NOT NULL, phone_number VARCHAR(20) NOT NULL, country VARCHAR(100) NOT NULL, registration_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, last_login DATETIME DEFAULT NULL, last_known_position JSON DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', available_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', delivered_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure ADD CONSTRAINT FK_9E858E0F7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_adventure_type ADD CONSTRAINT FK_AEA3EC6255CF40F9 FOREIGN KEY (adventure_id) REFERENCES adventure (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_adventure_type ADD CONSTRAINT FK_AEA3EC629046F2F7 FOREIGN KEY (adventure_type_id) REFERENCES adventure_type (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_user ADD CONSTRAINT FK_575C8E7455CF40F9 FOREIGN KEY (adventure_id) REFERENCES adventure (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_user ADD CONSTRAINT FK_575C8E74A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_file ADD CONSTRAINT FK_56506E2D55CF40F9 FOREIGN KEY (adventure_id) REFERENCES adventure (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_point ADD CONSTRAINT FK_EF17706D55CF40F9 FOREIGN KEY (adventure_id) REFERENCES adventure (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_alert ADD CONSTRAINT FK_EBB6D2355CF40F9 FOREIGN KEY (adventure_id) REFERENCES adventure (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_alert ADD CONSTRAINT FK_EBB6D2363C5923F FOREIGN KEY (triggered_by_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_alert_safety_contact ADD CONSTRAINT FK_5B9BC5CFD0AA4269 FOREIGN KEY (safety_alert_id) REFERENCES safety_alert (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_alert_safety_contact ADD CONSTRAINT FK_5B9BC5CF2D91C1A0 FOREIGN KEY (safety_contact_id) REFERENCES safety_contact (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact ADD CONSTRAINT FK_8C969750A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE timer_alert ADD CONSTRAINT FK_6A59BDF72793CC5E FOREIGN KEY (updated_by_user_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE timer_alert ADD CONSTRAINT FK_6A59BDF755CF40F9 FOREIGN KEY (adventure_id) REFERENCES adventure (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure DROP FOREIGN KEY FK_9E858E0F7E3C61F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_adventure_type DROP FOREIGN KEY FK_AEA3EC6255CF40F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_adventure_type DROP FOREIGN KEY FK_AEA3EC629046F2F7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_user DROP FOREIGN KEY FK_575C8E7455CF40F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_user DROP FOREIGN KEY FK_575C8E74A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_file DROP FOREIGN KEY FK_56506E2D55CF40F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_point DROP FOREIGN KEY FK_EF17706D55CF40F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_alert DROP FOREIGN KEY FK_EBB6D2355CF40F9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_alert DROP FOREIGN KEY FK_EBB6D2363C5923F
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_alert_safety_contact DROP FOREIGN KEY FK_5B9BC5CFD0AA4269
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_alert_safety_contact DROP FOREIGN KEY FK_5B9BC5CF2D91C1A0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact DROP FOREIGN KEY FK_8C969750A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE timer_alert DROP FOREIGN KEY FK_6A59BDF72793CC5E
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE timer_alert DROP FOREIGN KEY FK_6A59BDF755CF40F9
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE adventure
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE adventure_adventure_type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE adventure_user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE adventure_file
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE adventure_point
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE adventure_type
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE safety_alert
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE safety_alert_safety_contact
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE safety_contact
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE timer_alert
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE messenger_messages
        SQL);
    }
}
