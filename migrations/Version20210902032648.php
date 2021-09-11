<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210902032648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add template flag to Tasks, allowing you to create tasks with pre-defined subtasks';
    }

    public function upPostgresql(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task ADD template BOOLEAN');
        $this->addSql('UPDATE task SET template = false');
        $this->addSql('ALTER TABLE task ALTER COLUMN template SET NOT NULL');
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP template');
    }

    protected function upMysql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task ADD template TINYINT(1)');
        $this->addSql('UPDATE task SET template = false');
        $this->addSql('ALTER TABLE task CHANGE template template TINYINT(1) NOT NULL');
    }

    protected function downMysql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP template');
    }

    public function upSqlite(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_527EDB25727ACA70');
        $this->addSql('DROP INDEX IDX_527EDB25F4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, assigned_to_id, parent_id, name, description, canonical_name, priority, created_at, completed_at, updated_at, due_at, deleted_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , parent_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL COLLATE BINARY, description CLOB NOT NULL COLLATE BINARY, canonical_name VARCHAR(255) NOT NULL COLLATE BINARY, priority INTEGER NOT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, due_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, template BOOLEAN NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_527EDB25F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_527EDB25727ACA70 FOREIGN KEY (parent_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO task (id, assigned_to_id, parent_id, name, description, canonical_name, priority, created_at, completed_at, updated_at, due_at, deleted_at, template) SELECT id, assigned_to_id, parent_id, name, description, canonical_name, priority, created_at, completed_at, updated_at, due_at, deleted_at, false FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25727ACA70 ON task (parent_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25F4BD7827 ON task (assigned_to_id)');
    }

    public function downSqlite(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_527EDB25F4BD7827');
        $this->addSql('DROP INDEX IDX_527EDB25727ACA70');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, assigned_to_id, parent_id, completed_at, name, canonical_name, description, priority, due_at, created_at, updated_at, deleted_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , parent_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , completed_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, canonical_name VARCHAR(255) NOT NULL, description CLOB NOT NULL, priority INTEGER NOT NULL, due_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO task (id, assigned_to_id, parent_id, completed_at, name, canonical_name, description, priority, due_at, created_at, updated_at, deleted_at) SELECT id, assigned_to_id, parent_id, completed_at, name, canonical_name, description, priority, due_at, created_at, updated_at, deleted_at FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25F4BD7827 ON task (assigned_to_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25727ACA70 ON task (parent_id)');
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        switch ($platformName) {
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
                throw new Exception("Unsupported database '{$platformName}'");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        switch ($platformName) {
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
                throw new Exception("Unsupported database '{$platformName}'");
        }
    }
}
