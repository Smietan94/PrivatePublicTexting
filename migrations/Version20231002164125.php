<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231002164125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE friend_requests_history_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE friend_requests_history (id INT NOT NULL, requesting_user_id INT NOT NULL, requested_user_id INT NOT NULL, status INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_3112A4C12A841BBC ON friend_requests_history (requesting_user_id)');
        $this->addSql('CREATE INDEX IDX_3112A4C165A2CAD1 ON friend_requests_history (requested_user_id)');
        $this->addSql('ALTER TABLE friend_requests_history ADD CONSTRAINT FK_3112A4C12A841BBC FOREIGN KEY (requesting_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_requests_history ADD CONSTRAINT FK_3112A4C165A2CAD1 FOREIGN KEY (requested_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE friend_requests_history_id_seq CASCADE');
        $this->addSql('ALTER TABLE friend_requests_history DROP CONSTRAINT FK_3112A4C12A841BBC');
        $this->addSql('ALTER TABLE friend_requests_history DROP CONSTRAINT FK_3112A4C165A2CAD1');
        $this->addSql('DROP TABLE friend_requests_history');
    }
}
