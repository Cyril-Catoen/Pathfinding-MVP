<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250519215953 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE safety_contact_contact_list (safety_contact_id INT NOT NULL, contact_list_id INT NOT NULL, INDEX IDX_EA3A96342D91C1A0 (safety_contact_id), INDEX IDX_EA3A9634A781370A (contact_list_id), PRIMARY KEY(safety_contact_id, contact_list_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact_contact_list ADD CONSTRAINT FK_EA3A96342D91C1A0 FOREIGN KEY (safety_contact_id) REFERENCES safety_contact (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact_contact_list ADD CONSTRAINT FK_EA3A9634A781370A FOREIGN KEY (contact_list_id) REFERENCES contact_list (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact DROP FOREIGN KEY FK_8C969750A781370A
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_8C969750A781370A ON safety_contact
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact DROP contact_list_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact_contact_list DROP FOREIGN KEY FK_EA3A96342D91C1A0
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact_contact_list DROP FOREIGN KEY FK_EA3A9634A781370A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE safety_contact_contact_list
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact ADD contact_list_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE safety_contact ADD CONSTRAINT FK_8C969750A781370A FOREIGN KEY (contact_list_id) REFERENCES contact_list (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_8C969750A781370A ON safety_contact (contact_list_id)
        SQL);
    }
}
