<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422010206 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds date and duration formats to user as settings.';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE users ADD date_format VARCHAR(255)');
        $this->addSql("UPDATE users SET date_format = 'h:i:s A'");
        $this->addSql('ALTER TABLE users ALTER COLUMN date_format SET NOT NULL');

        $this->addSql('ALTER TABLE users ADD duration_format VARCHAR(255)');
        $this->addSql("UPDATE users SET duration_format = '%hh %Im %Ss'");
        $this->addSql('ALTER TABLE users ALTER COLUMN duration_format SET NOT NULL');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE users DROP date_format');
        $this->addSql('ALTER TABLE users DROP duration_format');
    }

    protected function upMysql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE time_entry CHANGE created_at created_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD date_format VARCHAR(255) NOT NULL, ADD duration_format VARCHAR(255) NOT NULL');
    }

    protected function downMysql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE time_entry CHANGE created_at created_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users DROP date_format, DROP duration_format');
    }

    protected function upSqlite(Schema $schema) : void
    {
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, description, created_at, ended_at, deleted_at FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , owner_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , description CLOB NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_6E537C0C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO time_entry (id, owner_id, description, created_at, ended_at, deleted_at) SELECT id, owner_id, description, created_at, ended_at, deleted_at FROM __temp__time_entry');
        $this->addSql('DROP TABLE __temp__time_entry');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');
        $this->addSql('DROP INDEX IDX_6243C23D1EB30A8E');
        $this->addSql('DROP INDEX IDX_6243C23DBAD26311');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry_tag AS SELECT id, time_entry_id, tag_id FROM time_entry_tag');
        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('CREATE TABLE time_entry_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , CONSTRAINT FK_6243C23D1EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6243C23DBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO time_entry_tag (id, time_entry_id, tag_id) SELECT id, time_entry_id, tag_id FROM __temp__time_entry_tag');
        $this->addSql('DROP TABLE __temp__time_entry_tag');
        $this->addSql('CREATE INDEX IDX_6243C23D1EB30A8E ON time_entry_tag (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_6243C23DBAD26311 ON time_entry_tag (tag_id)');
        $this->addSql('ALTER TABLE users ADD COLUMN date_format VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE users ADD COLUMN duration_format VARCHAR(255) NOT NULL');
    }

    protected function downSqlite(Schema $schema) : void
    {
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, created_at, ended_at, deleted_at, description FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , owner_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , description CLOB NOT NULL, created_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO time_entry (id, owner_id, created_at, ended_at, deleted_at, description) SELECT id, owner_id, created_at, ended_at, deleted_at, description FROM __temp__time_entry');
        $this->addSql('DROP TABLE __temp__time_entry');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');
        $this->addSql('DROP INDEX IDX_6243C23D1EB30A8E');
        $this->addSql('DROP INDEX IDX_6243C23DBAD26311');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry_tag AS SELECT id, time_entry_id, tag_id FROM time_entry_tag');
        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('CREATE TABLE time_entry_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL --(DC2Type:uuid)
        )');
        $this->addSql('INSERT INTO time_entry_tag (id, time_entry_id, tag_id) SELECT id, time_entry_id, tag_id FROM __temp__time_entry_tag');
        $this->addSql('DROP TABLE __temp__time_entry_tag');
        $this->addSql('CREATE INDEX IDX_6243C23D1EB30A8E ON time_entry_tag (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_6243C23DBAD26311 ON time_entry_tag (tag_id)');
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, timezone, email, username, enabled, confirmation_token, roles, password FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, password_requested_at, is_verified, timezone, email, username, enabled, confirmation_token, roles, password) SELECT id, password_requested_at, is_verified, timezone, email, username, enabled, confirmation_token, roles, password FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        switch ($platformName)
        {
            case 'sqlite':
                $this->upSqlite($schema);
                break;
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
            case 'sqlite':
                $this->downSqlite($schema);
                break;
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
