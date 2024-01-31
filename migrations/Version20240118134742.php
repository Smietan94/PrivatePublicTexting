<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240118134742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE notifications_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE notifications (id INT NOT NULL, sender_id INT DEFAULT NULL, notification_type INT NOT NULL, displayed BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6000B0D3F624B39D ON notifications (sender_id)');
        $this->addSql('CREATE TABLE notification_user (notification_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(notification_id, user_id))');
        $this->addSql('CREATE INDEX IDX_35AF9D73EF1A9D84 ON notification_user (notification_id)');
        $this->addSql('CREATE INDEX IDX_35AF9D73A76ED395 ON notification_user (user_id)');
        $this->addSql('ALTER TABLE notifications ADD CONSTRAINT FK_6000B0D3F624B39D FOREIGN KEY (sender_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification_user ADD CONSTRAINT FK_35AF9D73EF1A9D84 FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notification_user ADD CONSTRAINT FK_35AF9D73A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE notifications_id_seq CASCADE');
        $this->addSql('ALTER TABLE notifications DROP CONSTRAINT FK_6000B0D3F624B39D');
        $this->addSql('ALTER TABLE notification_user DROP CONSTRAINT FK_35AF9D73EF1A9D84');
        $this->addSql('ALTER TABLE notification_user DROP CONSTRAINT FK_35AF9D73A76ED395');
        $this->addSql('DROP TABLE notifications');
        $this->addSql('DROP TABLE notification_user');
    }
}
