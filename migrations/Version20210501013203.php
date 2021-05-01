<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210501013203 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Creates a task table and associates it to time entries';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE task (id UUID NOT NULL, created_by_id UUID NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_527EDB25B03A8386 ON task (created_by_id)');
        $this->addSql('COMMENT ON COLUMN task.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN task.created_by_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE time_entry ADD task_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN time_entry.task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0C8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE time_entry DROP CONSTRAINT FK_6E537C0C8DB60186');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('ALTER TABLE time_entry DROP task_id');
    }

    protected function upMysql(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_by_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, INDEX IDX_527EDB25B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE time_entry ADD task_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\', CHANGE created_at created_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0C8DB60186 FOREIGN KEY (task_id) REFERENCES task (id)');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
    }

    protected function downMysql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE time_entry DROP FOREIGN KEY FK_6E537C0C8DB60186');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186 ON time_entry');
        $this->addSql('ALTER TABLE time_entry DROP task_id, CHANGE created_at created_at DATETIME NOT NULL, CHANGE started_at started_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE ended_at ended_at DATETIME DEFAULT NULL, CHANGE deleted_at deleted_at DATETIME DEFAULT NULL');
    }

    protected function upSqlite(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, description CLOB NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_527EDB25B03A8386 ON task (created_by_id)');
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, description, created_at, ended_at, deleted_at, started_at, updated_at FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , owner_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , description CLOB NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, started_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_6E537C0C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6E537C0C8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO time_entry (id, owner_id, description, created_at, ended_at, deleted_at, started_at, updated_at) SELECT id, owner_id, description, created_at, ended_at, deleted_at, started_at, updated_at FROM __temp__time_entry');
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
    }

    protected function downSqlite(Schema $schema) : void
    {
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, created_at, started_at, updated_at, ended_at, deleted_at, description FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , owner_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , description CLOB NOT NULL, created_at DATETIME NOT NULL, started_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO time_entry (id, owner_id, created_at, started_at, updated_at, ended_at, deleted_at, description) SELECT id, owner_id, created_at, started_at, updated_at, ended_at, deleted_at, description FROM __temp__time_entry');
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
