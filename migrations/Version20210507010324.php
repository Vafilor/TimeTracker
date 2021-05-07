<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210507010324 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Creates timestamp resource, to keep track of events like a server going down every so often.';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        $this->addSql('CREATE SEQUENCE timestamp_tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE timestamp (id UUID NOT NULL, created_by_id UUID NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
        $this->addSql('COMMENT ON COLUMN timestamp.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN timestamp.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE timestamp_tag (id INT NOT NULL, tag_id UUID NOT NULL, timestamp_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_82A7B807BAD26311 ON timestamp_tag (tag_id)');
        $this->addSql('CREATE INDEX IDX_82A7B8072F202E84 ON timestamp_tag (timestamp_id)');
        $this->addSql('COMMENT ON COLUMN timestamp_tag.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN timestamp_tag.timestamp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE timestamp ADD CONSTRAINT FK_A5D6E63EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT FK_82A7B807BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT FK_82A7B8072F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE timestamp_tag DROP CONSTRAINT FK_82A7B8072F202E84');
        $this->addSql('DROP SEQUENCE timestamp_tag_id_seq CASCADE');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('DROP TABLE timestamp_tag');
    }

    protected function upMysql(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL, INDEX IDX_A5D6E63EB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE timestamp_tag (id INT AUTO_INCREMENT NOT NULL, tag_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', timestamp_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_82A7B807BAD26311 (tag_id), INDEX IDX_82A7B8072F202E84 (timestamp_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE timestamp ADD CONSTRAINT FK_A5D6E63EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT FK_82A7B807BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id)');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT FK_82A7B8072F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id)');
        $this->addSql('ALTER TABLE task CHANGE created_at created_at DATETIME NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE time_entry CHANGE created_at created_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME NOT NULL');
    }

    protected function downMysql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE timestamp_tag DROP FOREIGN KEY FK_82A7B8072F202E84');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('DROP TABLE timestamp_tag');
        $this->addSql('ALTER TABLE task CHANGE created_at created_at DATETIME NOT NULL, CHANGE completed_at completed_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE time_entry CHANGE created_at created_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL');
    }

    protected function upSqlite(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
        $this->addSql('CREATE TABLE timestamp_tag (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, tag_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , timestamp_id CHAR(36) NOT NULL --(DC2Type:uuid)
        )');
        $this->addSql('CREATE INDEX IDX_82A7B807BAD26311 ON timestamp_tag (tag_id)');
        $this->addSql('CREATE INDEX IDX_82A7B8072F202E84 ON timestamp_tag (timestamp_id)');
        $this->addSql('DROP INDEX IDX_527EDB25B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, created_by_id, created_at, completed_at, name, description, updated_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL COLLATE BINARY, description CLOB NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_527EDB25B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO task (id, created_by_id, created_at, completed_at, name, description, updated_at) SELECT id, created_by_id, created_at, completed_at, name, description, updated_at FROM __temp__task');
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
    }

    protected function downSqlite(Schema $schema) : void
    {
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('DROP TABLE timestamp_tag');
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
