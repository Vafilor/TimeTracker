<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210613213539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates Tasks to have a priority, canonical name, and tags.';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag_link ADD task_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN tag_link.task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326478DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8A326478DB60186 ON tag_link (task_id)');

        $this->addSql('ALTER TABLE task ADD canonical_name VARCHAR(255)');
        $this->addSql('UPDATE task SET canonical_name = LOWER(name)');
        $this->addSql('ALTER TABLE task ALTER COLUMN canonical_name SET NOT NULL');

        $this->addSql('ALTER TABLE task ADD priority INT');
        $this->addSql('UPDATE task SET priority = 0');
        $this->addSql('ALTER TABLE task ALTER COLUMN priority SET NOT NULL');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP canonical_name');
        $this->addSql('ALTER TABLE task DROP priority');
        $this->addSql('ALTER TABLE tag_link DROP CONSTRAINT FK_D8A326478DB60186');
        $this->addSql('DROP INDEX IDX_D8A326478DB60186');
        $this->addSql('ALTER TABLE tag_link DROP task_id');
    }

    protected function upMysql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag_link ADD task_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326478DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
        $this->addSql('CREATE INDEX IDX_D8A326478DB60186 ON tag_link (task_id)');
        $this->addSql('ALTER TABLE task ADD canonical_name VARCHAR(255), ADD priority INT');

        $this->addSql('UPDATE task SET canonical_name = LOWER(name), priority = 0');
        $this->addSql('ALTER TABLE task CHANGE canonical_name canonical_name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE task CHANGE priority priority INT NOT NULL');
    }

    protected function downMysql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag_link DROP FOREIGN KEY FK_D8A326478DB60186');
        $this->addSql('DROP INDEX IDX_D8A326478DB60186 ON tag_link');
        $this->addSql('ALTER TABLE tag_link DROP task_id');
        $this->addSql('ALTER TABLE task DROP canonical_name, DROP priority');
    }

    protected function upSqlite(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_389B783B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, created_by_id, name, color, created_at FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL COLLATE BINARY, color VARCHAR(7) NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_389B783B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO tag (id, created_by_id, name, color, created_at) SELECT id, created_by_id, name, color, created_at FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
        $this->addSql('CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)');
        $this->addSql('DROP INDEX IDX_D8A32647BAD26311');
        $this->addSql('DROP INDEX IDX_D8A326472F202E84');
        $this->addSql('DROP INDEX IDX_D8A326471EB30A8E');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag_link AS SELECT id, time_entry_id, timestamp_id, tag_id FROM tag_link');
        $this->addSql('DROP TABLE tag_link');
        $this->addSql('CREATE TABLE tag_link (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , timestamp_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , CONSTRAINT FK_D8A326471EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8A326472F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8A326478DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8A32647BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO tag_link (id, time_entry_id, timestamp_id, tag_id) SELECT id, time_entry_id, timestamp_id, tag_id FROM __temp__tag_link');
        $this->addSql('DROP TABLE __temp__tag_link');
        $this->addSql('CREATE INDEX IDX_D8A32647BAD26311 ON tag_link (tag_id)');
        $this->addSql('CREATE INDEX IDX_D8A326472F202E84 ON tag_link (timestamp_id)');
        $this->addSql('CREATE INDEX IDX_D8A326471EB30A8E ON tag_link (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_D8A326478DB60186 ON tag_link (task_id)');
        $this->addSql('DROP INDEX IDX_527EDB25B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, created_by_id, name, description, created_at, completed_at, updated_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL COLLATE BINARY, description CLOB NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, canonical_name VARCHAR(255) NOT NULL, priority INTEGER NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_527EDB25B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO task (id, created_by_id, name, description, created_at, completed_at, updated_at, canonical_name, priority) SELECT id, created_by_id, name, description, created_at, completed_at, updated_at, LOWER(name), 0 FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25B03A8386 ON task (created_by_id)');
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , owner_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , description CLOB NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, started_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_6E537C0C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6E537C0C8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO time_entry (id, owner_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at) SELECT id, owner_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at FROM __temp__time_entry');
        $this->addSql('DROP TABLE __temp__time_entry');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
        $this->addSql('DROP INDEX IDX_A5D6E63EB03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, created_by_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_A5D6E63EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO timestamp (id, created_by_id, created_at) SELECT id, created_by_id, created_at FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677');
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL COLLATE BINARY, date_format VARCHAR(255) NOT NULL COLLATE BINARY, today_date_format VARCHAR(255) NOT NULL COLLATE BINARY, duration_format VARCHAR(255) NOT NULL COLLATE BINARY, email VARCHAR(180) NOT NULL COLLATE BINARY, username VARCHAR(180) NOT NULL COLLATE BINARY, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL COLLATE BINARY, roles CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , password VARCHAR(255) NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at) SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    protected function downSqlite(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_389B783B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, created_by_id, name, color, created_at FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, color VARCHAR(7) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO tag (id, created_by_id, name, color, created_at) SELECT id, created_by_id, name, color, created_at FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
        $this->addSql('CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)');
        $this->addSql('DROP INDEX IDX_D8A326471EB30A8E');
        $this->addSql('DROP INDEX IDX_D8A326472F202E84');
        $this->addSql('DROP INDEX IDX_D8A326478DB60186');
        $this->addSql('DROP INDEX IDX_D8A32647BAD26311');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag_link AS SELECT id, time_entry_id, timestamp_id, tag_id FROM tag_link');
        $this->addSql('DROP TABLE tag_link');
        $this->addSql('CREATE TABLE tag_link (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , timestamp_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL --(DC2Type:uuid)
        )');
        $this->addSql('INSERT INTO tag_link (id, time_entry_id, timestamp_id, tag_id) SELECT id, time_entry_id, timestamp_id, tag_id FROM __temp__tag_link');
        $this->addSql('DROP TABLE __temp__tag_link');
        $this->addSql('CREATE INDEX IDX_D8A326471EB30A8E ON tag_link (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_D8A326472F202E84 ON tag_link (timestamp_id)');
        $this->addSql('CREATE INDEX IDX_D8A32647BAD26311 ON tag_link (tag_id)');
        $this->addSql('DROP INDEX IDX_527EDB25B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, created_by_id, completed_at, name, description, created_at, updated_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, description CLOB NOT NULL, completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO task (id, created_by_id, completed_at, name, description, created_at, updated_at) SELECT id, created_by_id, completed_at, name, description, created_at, updated_at FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25B03A8386 ON task (created_by_id)');
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, task_id, started_at, ended_at, deleted_at, description, created_at, updated_at FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , owner_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , description CLOB NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO time_entry (id, owner_id, task_id, started_at, ended_at, deleted_at, description, created_at, updated_at) SELECT id, owner_id, task_id, started_at, ended_at, deleted_at, description, created_at, updated_at FROM __temp__time_entry');
        $this->addSql('DROP TABLE __temp__time_entry');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
        $this->addSql('DROP INDEX IDX_A5D6E63EB03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, created_by_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO timestamp (id, created_by_id, created_at) SELECT id, created_by_id, created_at FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL, date_format VARCHAR(255) NOT NULL, today_date_format VARCHAR(255) NOT NULL, duration_format VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at) SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM __temp__users');
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
