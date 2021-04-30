<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210420055958 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Sets up the basic tables: tag, time_entry, users, and the relations between time entries and tags.';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        $this->addSql('CREATE SEQUENCE time_entry_tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE tag (id UUID NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_389B7835E237E06 ON tag (name)');
        $this->addSql('COMMENT ON COLUMN tag.id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE time_entry (id UUID NOT NULL, owner_id UUID NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, ended_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, deleted_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');
        $this->addSql('COMMENT ON COLUMN time_entry.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN time_entry.owner_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE time_entry_tag (id INT NOT NULL, time_entry_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_6243C23D1EB30A8E ON time_entry_tag (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_6243C23DBAD26311 ON time_entry_tag (tag_id)');
        $this->addSql('COMMENT ON COLUMN time_entry_tag.time_entry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN time_entry_tag.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE users (id UUID NOT NULL, password_requested_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
        $this->addSql('COMMENT ON COLUMN users.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT FK_6243C23D1EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT FK_6243C23DBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE time_entry_tag DROP CONSTRAINT FK_6243C23DBAD26311');
        $this->addSql('ALTER TABLE time_entry_tag DROP CONSTRAINT FK_6243C23D1EB30A8E');
        $this->addSql('ALTER TABLE time_entry DROP CONSTRAINT FK_6E537C0C7E3C61F9');
        $this->addSql('DROP SEQUENCE time_entry_tag_id_seq CASCADE');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('DROP TABLE users');
    }

    protected function upMysql(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_389B7835E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', owner_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, description LONGTEXT NOT NULL, INDEX IDX_6E537C0C7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE time_entry_tag (id INT AUTO_INCREMENT NOT NULL, time_entry_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', tag_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_6243C23D1EB30A8E (time_entry_id), INDEX IDX_6243C23DBAD26311 (tag_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', password_requested_at DATETIME DEFAULT NULL, is_verified TINYINT(1) NOT NULL, timezone VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, enabled TINYINT(1) NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT FK_6243C23D1EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id)');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT FK_6243C23DBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
    }

    protected function downMysql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE time_entry_tag DROP FOREIGN KEY FK_6243C23DBAD26311');
        $this->addSql('ALTER TABLE time_entry_tag DROP FOREIGN KEY FK_6243C23D1EB30A8E');
        $this->addSql('ALTER TABLE time_entry DROP FOREIGN KEY FK_6E537C0C7E3C61F9');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('DROP TABLE users');
    }


    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        switch ($platformName)
        {
            case 'postgresql':
                $this->upPostgresql($schema);
                break;
            case 'mysql':
                $this->upMysql($schema);
                break;
            default:
                throw new \Exception("Unsupported database '{$platformName}'");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        switch ($platformName)
        {
            case 'postgresql':
                $this->downPostgresql($schema);
                break;
            case 'mysql':
                $this->downMysql($schema);
                break;
            default:
                throw new \Exception("Unsupported database '{$platformName}'");
        }
    }
}
