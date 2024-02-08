<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240205203544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification_user DROP CONSTRAINT fk_35af9d73ef1a9d84');
        $this->addSql('ALTER TABLE notification_user DROP CONSTRAINT fk_35af9d73a76ed395');
        $this->addSql('DROP TABLE notification_user');
        $this->addSql('ALTER TABLE notifications ADD receiver_id INT NOT NULL');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6000B0D3CD53EDB6 ON notifications (receiver_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE TABLE notification_user (notification_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(notification_id, user_id))');
        $this->addSql('CREATE INDEX idx_35af9d73a76ed395 ON notification_user (user_id)');
        $this->addSql('CREATE INDEX idx_35af9d73ef1a9d84 ON notification_user (notification_id)');
        $this->addSql('ALTER TABLE notification_user ADD CONSTRAINT fk_35af9d73ef1a9d84 FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification_user ADD CONSTRAINT fk_35af9d73a76ed395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3CD53EDB6');
        $this->addSql('DROP INDEX IDX_6000B0D3CD53EDB6');
        $this->addSql('ALTER TABLE notifications DROP receiver_id');
    }
}
