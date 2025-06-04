<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250531104608 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE adventure_pictures (id INT AUTO_INCREMENT NOT NULL, adventure_id INT NOT NULL, picture_path VARCHAR(255) NOT NULL, uploaded_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', position INT DEFAULT NULL, INDEX IDX_C5F680FF55CF40F9 (adventure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_pictures ADD CONSTRAINT FK_C5F680FF55CF40F9 FOREIGN KEY (adventure_id) REFERENCES adventure (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure_pictures DROP FOREIGN KEY FK_C5F680FF55CF40F9
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE adventure_pictures
        SQL);
    }
}
