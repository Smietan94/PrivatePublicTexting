<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240320202926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // $this->addSql('ALTER TABLE conversations ADD last_message_id INT DEFAULT NULL');
        // $this->addSql('ALTER TABLE conversations ADD CONSTRAINT FK_C2521BF1BA0E79C3 FOREIGN KEY (last_message_id) REFERENCES messages (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        // $this->addSql('CREATE UNIQUE INDEX UNIQ_C2521BF1BA0E79C3 ON conversations (last_message_id)');
        // $this->addSql('ALTER TABLE messages ADD conversation_id INT NOT NULL');
        // $this->addSql('ALTER TABLE messages ADD CONSTRAINT FK_DB021E969AC0396 FOREIGN KEY (conversation_id) REFERENCES conversations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        // $this->addSql('CREATE INDEX IDX_DB021E969AC0396 ON messages (conversation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE conversations DROP CONSTRAINT FK_C2521BF1BA0E79C3');
        $this->addSql('DROP INDEX UNIQ_C2521BF1BA0E79C3');
        $this->addSql('ALTER TABLE conversations DROP last_message_id');
        $this->addSql('ALTER TABLE messages DROP CONSTRAINT FK_DB021E969AC0396');
        $this->addSql('DROP INDEX IDX_DB021E969AC0396');
        $this->addSql('ALTER TABLE messages DROP conversation_id');
    }
}
