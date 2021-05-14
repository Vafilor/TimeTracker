<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210514005926 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds createdBy, createdAt to tag table and adds createdAt to users.';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag ADD created_by_id UUID');
        $this->addSql('UPDATE tag SET created_by_id = (SELECT id FROM users ORDER BY id ASC LIMIT 1)');
        $this->addSql('ALTER TABLE tag ALTER COLUMN created_by_id SET NOT NULL');

        $this->addSql('ALTER TABLE tag ADD created_at TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('UPDATE tag SET created_at = now()');
        $this->addSql('ALTER TABLE tag ALTER COLUMN created_at SET NOT NULL');

        $this->addSql('COMMENT ON COLUMN tag.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B783B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)');

        // Remove unique name constraint since different users may have the same tag names
        $this->addSql('DROP INDEX IF EXISTS uniq_389b7835e237e06');

        $this->addSql('ALTER TABLE users ADD created_at TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('UPDATE users SET created_at = now()');
        $this->addSql('ALTER TABLE users ALTER COLUMN created_at SET NOT NULL');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP created_at');
        $this->addSql('ALTER TABLE tag DROP CONSTRAINT FK_389B783B03A8386');
        $this->addSql('DROP INDEX IDX_389B783B03A8386');
        $this->addSql('ALTER TABLE tag DROP created_by_id');
        $this->addSql('ALTER TABLE tag DROP created_at');
    }

    protected function upMysql(Schema $schema) : void
    {
        $this->addSql('DROP INDEX UNIQ_389B7835E237E06 ON tag');

        $this->addSql('ALTER TABLE tag ADD created_by_id CHAR(36) COMMENT \'(DC2Type:uuid)\', ADD created_at DATETIME');
        $this->addSql('UPDATE tag SET created_by_id = (SELECT id FROM users ORDER BY id ASC LIMIT 1)');
        $this->addSql('UPDATE tag SET created_at = now()');
        $this->addSql('ALTER TABLE tag CHANGE created_by_id created_by_id CHAR(36) NOT NULL');
        $this->addSql('ALTER TABLE tag CHANGE created_at created_at DATETIME NOT NULL');

        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B783B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)');
        $this->addSql('ALTER TABLE task CHANGE created_at created_at DATETIME NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE time_entry CHANGE created_at created_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE timestamp CHANGE created_at created_at DATETIME NOT NULL');

        $this->addSql('ALTER TABLE users ADD created_at DATETIME');
        $this->addSql('UPDATE users SET created_at = now()');
        $this->addSql('ALTER TABLE users CHANGE created_at created_at DATETIME NOT NULL');
    }

    protected function downMysql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B783B03A8386');
        $this->addSql('DROP INDEX IDX_389B783B03A8386 ON tag');
        $this->addSql('ALTER TABLE tag DROP created_by_id, DROP created_at');
        $this->addSql('ALTER TABLE task CHANGE created_at created_at DATETIME NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE time_entry CHANGE created_at created_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE timestamp CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE users DROP created_at');
    }

    protected function upSqlite(Schema $schema) : void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_389B7835E237E06');
        $this->addSql("CREATE TEMPORARY TABLE __temp__tag AS SELECT id, name, color, (SELECT id FROM users ORDER BY id DESC LIMIT 1) as created_by_id, strftime('%Y-%m-%d %H:%M:%S','now') as created_at FROM tag");
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL COLLATE BINARY, color VARCHAR(7) NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_389B783B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO tag (id, created_by_id, name, color, created_at) SELECT id, created_by_id, name, color, created_at FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
        $this->addSql('CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)');
        $this->addSql('DROP INDEX IDX_527EDB25B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, created_by_id, name, description, created_at, completed_at, updated_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL COLLATE BINARY, description CLOB NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_527EDB25B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO task (id, created_by_id, name, description, created_at, completed_at, updated_at) SELECT id, created_by_id, name, description, created_at, completed_at, updated_at FROM __temp__task');
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
        $this->addSql('DROP INDEX IDX_6243C23DBAD26311');
        $this->addSql('DROP INDEX IDX_6243C23D1EB30A8E');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry_tag AS SELECT id, time_entry_id, tag_id FROM time_entry_tag');
        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('CREATE TABLE time_entry_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , CONSTRAINT FK_6243C23D1EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6243C23DBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO time_entry_tag (id, time_entry_id, tag_id) SELECT id, time_entry_id, tag_id FROM __temp__time_entry_tag');
        $this->addSql('DROP TABLE __temp__time_entry_tag');
        $this->addSql('CREATE INDEX IDX_6243C23DBAD26311 ON time_entry_tag (tag_id)');
        $this->addSql('CREATE INDEX IDX_6243C23D1EB30A8E ON time_entry_tag (time_entry_id)');
        $this->addSql('DROP INDEX IDX_A5D6E63EB03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, created_by_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_A5D6E63EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO timestamp (id, created_by_id, created_at) SELECT id, created_by_id, created_at FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
        $this->addSql('DROP INDEX IDX_82A7B8072F202E84');
        $this->addSql('DROP INDEX IDX_82A7B807BAD26311');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp_tag AS SELECT id, tag_id, timestamp_id FROM timestamp_tag');
        $this->addSql('DROP TABLE timestamp_tag');
        $this->addSql('CREATE TABLE timestamp_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, tag_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , timestamp_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , CONSTRAINT FK_82A7B807BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_82A7B8072F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO timestamp_tag (id, tag_id, timestamp_id) SELECT id, tag_id, timestamp_id FROM __temp__timestamp_tag');
        $this->addSql('DROP TABLE __temp__timestamp_tag');
        $this->addSql('CREATE INDEX IDX_82A7B8072F202E84 ON timestamp_tag (timestamp_id)');
        $this->addSql('CREATE INDEX IDX_82A7B807BAD26311 ON timestamp_tag (tag_id)');

        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL COLLATE BINARY, date_format VARCHAR(255) NOT NULL COLLATE BINARY, today_date_format VARCHAR(255) NOT NULL COLLATE BINARY, duration_format VARCHAR(255) NOT NULL COLLATE BINARY, email VARCHAR(180) NOT NULL COLLATE BINARY, username VARCHAR(180) NOT NULL COLLATE BINARY, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL COLLATE BINARY, roles CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , password VARCHAR(255) NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql("INSERT INTO users (id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at) SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, strftime('%Y-%m-%d %H:%M:%S','now') FROM __temp__users");
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    protected function downSqlite(Schema $schema) : void
    {
        $this->addSql('DROP INDEX IDX_389B783B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, name, color FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, color VARCHAR(7) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO tag (id, name, color) SELECT id, name, color FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
        $this->addSql('DROP INDEX IDX_527EDB25B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, created_by_id, created_at, completed_at, name, description, updated_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, description CLOB NOT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO task (id, created_by_id, created_at, completed_at, name, description, updated_at) SELECT id, created_by_id, created_at, completed_at, name, description, updated_at FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25B03A8386 ON task (created_by_id)');
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, task_id, created_at, started_at, ended_at, deleted_at, description, updated_at FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , owner_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , description CLOB NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO time_entry (id, owner_id, task_id, created_at, started_at, ended_at, deleted_at, description, updated_at) SELECT id, owner_id, task_id, created_at, started_at, ended_at, deleted_at, description, updated_at FROM __temp__time_entry');
        $this->addSql('DROP TABLE __temp__time_entry');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
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
        $this->addSql('DROP INDEX IDX_A5D6E63EB03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, created_by_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO timestamp (id, created_by_id, created_at) SELECT id, created_by_id, created_at FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
        $this->addSql('DROP INDEX IDX_82A7B807BAD26311');
        $this->addSql('DROP INDEX IDX_82A7B8072F202E84');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp_tag AS SELECT id, tag_id, timestamp_id FROM timestamp_tag');
        $this->addSql('DROP TABLE timestamp_tag');
        $this->addSql('CREATE TABLE timestamp_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, tag_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , timestamp_id CHAR(36) NOT NULL --(DC2Type:uuid)
        )');
        $this->addSql('INSERT INTO timestamp_tag (id, tag_id, timestamp_id) SELECT id, tag_id, timestamp_id FROM __temp__timestamp_tag');
        $this->addSql('DROP TABLE __temp__timestamp_tag');
        $this->addSql('CREATE INDEX IDX_82A7B807BAD26311 ON timestamp_tag (tag_id)');
        $this->addSql('CREATE INDEX IDX_82A7B8072F202E84 ON timestamp_tag (timestamp_id)');
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL, date_format VARCHAR(255) NOT NULL, today_date_format VARCHAR(255) NOT NULL, duration_format VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password) SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password FROM __temp__users');
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
