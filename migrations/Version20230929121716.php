<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230929121716 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE friend_requests_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE friend_requests (id INT NOT NULL, requesting_user_id INT NOT NULL, requested_user_id INT NOT NULL, status INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EC63B01B2A841BBC ON friend_requests (requesting_user_id)');
        $this->addSql('CREATE INDEX IDX_EC63B01B65A2CAD1 ON friend_requests (requested_user_id)');
        $this->addSql('ALTER TABLE friend_requests ADD CONSTRAINT FK_EC63B01B2A841BBC FOREIGN KEY (requesting_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE friend_requests ADD CONSTRAINT FK_EC63B01B65A2CAD1 FOREIGN KEY (requested_user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE friend_requests_id_seq CASCADE');
        $this->addSql('ALTER TABLE friend_requests DROP CONSTRAINT FK_EC63B01B2A841BBC');
        $this->addSql('ALTER TABLE friend_requests DROP CONSTRAINT FK_EC63B01B65A2CAD1');
        $this->addSql('DROP TABLE friend_requests');
    }
}
