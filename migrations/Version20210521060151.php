<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210521060151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates the database structure for tag associations to use a single table instead of multiple.';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE time_entry_tag_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE timestamp_tag_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE tag_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE tag_link (id INT NOT NULL, time_entry_id UUID DEFAULT NULL, timestamp_id UUID DEFAULT NULL, tag_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D8A326471EB30A8E ON tag_link (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_D8A326472F202E84 ON tag_link (timestamp_id)');
        $this->addSql('CREATE INDEX IDX_D8A32647BAD26311 ON tag_link (tag_id)');
        $this->addSql('COMMENT ON COLUMN tag_link.time_entry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN tag_link.timestamp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN tag_link.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326471EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326472F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A32647BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Copy over old data
        $this->addSql("INSERT INTO tag_link(id, time_entry_id, tag_id) SELECT nextval('tag_link_id_seq'), time_entry_id, tag_id FROM time_entry_tag;");
        $this->addSql("INSERT INTO tag_link(id, timestamp_id, tag_id) SELECT nextval('tag_link_id_seq'), timestamp_id, tag_id FROM timestamp_tag;");

        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('DROP TABLE timestamp_tag');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE tag_link_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE time_entry_tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE timestamp_tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE time_entry_tag (id INT NOT NULL, time_entry_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_6243c23dbad26311 ON time_entry_tag (tag_id)');
        $this->addSql('CREATE INDEX idx_6243c23d1eb30a8e ON time_entry_tag (time_entry_id)');
        $this->addSql('COMMENT ON COLUMN time_entry_tag.time_entry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN time_entry_tag.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE timestamp_tag (id INT NOT NULL, tag_id UUID NOT NULL, timestamp_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_82a7b8072f202e84 ON timestamp_tag (timestamp_id)');
        $this->addSql('CREATE INDEX idx_82a7b807bad26311 ON timestamp_tag (tag_id)');
        $this->addSql('COMMENT ON COLUMN timestamp_tag.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN timestamp_tag.timestamp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT fk_6243c23d1eb30a8e FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT fk_6243c23dbad26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT fk_82a7b807bad26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT fk_82a7b8072f202e84 FOREIGN KEY (timestamp_id) REFERENCES "timestamp" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Copy over data
        $this->addSql("INSERT INTO time_entry_tag(id, time_entry_id, tag_id) SELECT nextval('time_entry_tag_id_seq'), time_entry_id, tag_id FROM tag_link WHERE time_entry_id IS NOT NULL");
        $this->addSql("INSERT INTO timestamp_tag(id, timestamp_id, tag_id) SELECT nextval('timestamp_tag_id_seq'), timestamp_id, tag_id FROM tag_link WHERE timestamp_id IS NOT NULL");

        $this->addSql('DROP TABLE tag_link');
    }

    protected function upMysql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tag_link (id INT AUTO_INCREMENT NOT NULL, time_entry_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', timestamp_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', tag_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_D8A326471EB30A8E (time_entry_id), INDEX IDX_D8A326472F202E84 (timestamp_id), INDEX IDX_D8A32647BAD26311 (tag_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326471EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id)');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326472F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id)');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A32647BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');

        // Copy over old data
        $this->addSql("INSERT INTO tag_link(time_entry_id, tag_id) SELECT time_entry_id, tag_id FROM time_entry_tag;");
        $this->addSql("INSERT INTO tag_link(timestamp_id, tag_id) SELECT timestamp_id, tag_id FROM timestamp_tag;");

        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('DROP TABLE timestamp_tag');
        $this->addSql('ALTER TABLE tag CHANGE created_by_id created_by_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE task CHANGE created_at created_at DATETIME NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE time_entry CHANGE created_at created_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE timestamp CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE created_at created_at DATETIME NOT NULL');
    }

    protected function downMysql(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE time_entry_tag (id INT AUTO_INCREMENT NOT NULL, time_entry_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', tag_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', INDEX IDX_6243C23D1EB30A8E (time_entry_id), INDEX IDX_6243C23DBAD26311 (tag_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE timestamp_tag (id INT AUTO_INCREMENT NOT NULL, tag_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', timestamp_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\', INDEX IDX_82A7B8072F202E84 (timestamp_id), INDEX IDX_82A7B807BAD26311 (tag_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT FK_6243C23D1EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT FK_6243C23DBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT FK_82A7B8072F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT FK_82A7B807BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE NO ACTION');

        // Copy over data
        $this->addSql("INSERT INTO time_entry_tag(time_entry_id, tag_id) SELECT time_entry_id, tag_id FROM tag_link WHERE time_entry_id IS NOT NULL");
        $this->addSql("INSERT INTO timestamp_tag(timestamp_id, tag_id) SELECT timestamp_id, tag_id FROM tag_link WHERE timestamp_id IS NOT NULL");

        $this->addSql('DROP TABLE tag_link');
        $this->addSql('ALTER TABLE tag CHANGE created_by_id created_by_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE task CHANGE created_at created_at DATETIME NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE time_entry CHANGE created_at created_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE timestamp CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE created_at created_at DATETIME NOT NULL');
    }

    protected function upSqlite(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tag_link (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , timestamp_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL --(DC2Type:uuid)
        )');
        $this->addSql('CREATE INDEX IDX_D8A326471EB30A8E ON tag_link (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_D8A326472F202E84 ON tag_link (timestamp_id)');
        $this->addSql('CREATE INDEX IDX_D8A32647BAD26311 ON tag_link (tag_id)');

        // Copy over old data
        $this->addSql("INSERT INTO tag_link(time_entry_id, tag_id) SELECT time_entry_id, tag_id FROM time_entry_tag;");
        $this->addSql("INSERT INTO tag_link(timestamp_id, tag_id) SELECT timestamp_id, tag_id FROM timestamp_tag;");

        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('DROP TABLE timestamp_tag');
        $this->addSql('DROP INDEX IDX_389B783B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, created_by_id, name, color, created_at FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
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
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , owner_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , description CLOB NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, started_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_6E537C0C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6E537C0C8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO time_entry (id, owner_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at) SELECT id, owner_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at FROM __temp__time_entry');
        $this->addSql('DROP TABLE __temp__time_entry');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');
        $this->addSql('DROP INDEX IDX_A5D6E63EB03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, created_by_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_A5D6E63EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO timestamp (id, created_by_id, created_at) SELECT id, created_by_id, created_at FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL COLLATE BINARY, date_format VARCHAR(255) NOT NULL COLLATE BINARY, today_date_format VARCHAR(255) NOT NULL COLLATE BINARY, duration_format VARCHAR(255) NOT NULL COLLATE BINARY, email VARCHAR(180) NOT NULL COLLATE BINARY, username VARCHAR(180) NOT NULL COLLATE BINARY, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL COLLATE BINARY, roles CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , password VARCHAR(255) NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at) SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
    }

    protected function downSqlite(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE time_entry_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        )');
        $this->addSql('CREATE INDEX IDX_6243C23D1EB30A8E ON time_entry_tag (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_6243C23DBAD26311 ON time_entry_tag (tag_id)');
        $this->addSql('CREATE TABLE timestamp_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, tag_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , timestamp_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        )');
        $this->addSql('CREATE INDEX IDX_82A7B807BAD26311 ON timestamp_tag (tag_id)');
        $this->addSql('CREATE INDEX IDX_82A7B8072F202E84 ON timestamp_tag (timestamp_id)');

        // Copy over data
        $this->addSql("INSERT INTO time_entry_tag(time_entry_id, tag_id) SELECT time_entry_id, tag_id FROM tag_link WHERE time_entry_id IS NOT NULL");
        $this->addSql("INSERT INTO timestamp_tag(timestamp_id, tag_id) SELECT timestamp_id, tag_id FROM tag_link WHERE timestamp_id IS NOT NULL");

        $this->addSql('DROP TABLE tag_link');
        $this->addSql('DROP INDEX IDX_389B783B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, created_by_id, created_at, name, color FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, color VARCHAR(7) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO tag (id, created_by_id, created_at, name, color) SELECT id, created_by_id, created_at, name, color FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
        $this->addSql('CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)');
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
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, created_at, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL, date_format VARCHAR(255) NOT NULL, today_date_format VARCHAR(255) NOT NULL, duration_format VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, password_requested_at, is_verified, created_at, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password) SELECT id, password_requested_at, is_verified, created_at, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password FROM __temp__users');
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
