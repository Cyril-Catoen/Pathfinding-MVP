<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250604191600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure ADD contact_list_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure ADD CONSTRAINT FK_9E858E0FA781370A FOREIGN KEY (contact_list_id) REFERENCES contact_list (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9E858E0FA781370A ON adventure (contact_list_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure DROP FOREIGN KEY FK_9E858E0FA781370A
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_9E858E0FA781370A ON adventure
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE adventure DROP contact_list_id
        SQL);
    }
}
