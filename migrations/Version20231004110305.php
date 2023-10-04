<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231004110305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE friend_requests_history_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE friend_history_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE friend_history (id INT NOT NULL, requesting_user_id INT NOT NULL, requested_user_id INT NOT NULL, status INT NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1305B2402A841BBC ON friend_history (requesting_user_id)');
        $this->addSql('CREATE INDEX IDX_1305B24065A2CAD1 ON friend_history (requested_user_id)');
        $this->addSql('ALTER TABLE friend_history ADD CONSTRAINT FK_1305B2402A841BBC FOREIGN KEY (requesting_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_history ADD CONSTRAINT FK_1305B24065A2CAD1 FOREIGN KEY (requested_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_requests_history DROP CONSTRAINT fk_3112a4c12a841bbc');
        $this->addSql('ALTER TABLE friend_requests_history DROP CONSTRAINT fk_3112a4c165a2cad1');
        $this->addSql('DROP TABLE friend_requests_history');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE friend_history_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE friend_requests_history_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE friend_requests_history (id INT NOT NULL, requesting_user_id INT NOT NULL, requested_user_id INT NOT NULL, status INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, sent_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_3112a4c165a2cad1 ON friend_requests_history (requested_user_id)');
        $this->addSql('CREATE INDEX idx_3112a4c12a841bbc ON friend_requests_history (requesting_user_id)');
        $this->addSql('ALTER TABLE friend_requests_history ADD CONSTRAINT fk_3112a4c12a841bbc FOREIGN KEY (requesting_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_requests_history ADD CONSTRAINT fk_3112a4c165a2cad1 FOREIGN KEY (requested_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_history DROP CONSTRAINT FK_1305B2402A841BBC');
        $this->addSql('ALTER TABLE friend_history DROP CONSTRAINT FK_1305B24065A2CAD1');
        $this->addSql('DROP TABLE friend_history');
    }
}
