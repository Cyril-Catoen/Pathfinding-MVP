<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519195149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE contact_list (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, name VARCHAR(100) NOT NULL, is_default TINYINT(1) NOT NULL, INDEX IDX_6C377AE77E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact_list ADD CONSTRAINT FK_6C377AE77E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact ADD contact_list_id INT DEFAULT NULL, ADD is_favorite TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact ADD CONSTRAINT FK_8C969750A781370A FOREIGN KEY (contact_list_id) REFERENCES contact_list (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8C969750A781370A ON safety_contact (contact_list_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact DROP FOREIGN KEY FK_8C969750A781370A
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE contact_list DROP FOREIGN KEY FK_6C377AE77E3C61F9
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE contact_list
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8C969750A781370A ON safety_contact
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact DROP contact_list_id, DROP is_favorite
        SQL);
    }
}
