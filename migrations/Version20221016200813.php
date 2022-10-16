<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\TimeTrackerMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20221016200813 extends TimeTrackerMigration
{
    public function getDescription(): string
    {
        return 'Adds an active flag to tasks';
    }

    public function upPostgresql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD active BOOLEAN');
        $this->addSql('UPDATE task SET active = FALSE');
        $this->addSql('ALTER TABLE task ALTER COLUMN active SET NOT NULL');
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP active');
    }

    protected function upMysql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD active TINYINT(1)');
        $this->addSql("UPDATE task SET active = FALSE");
        $this->addSql('ALTER TABLE task CHANGE active active TINYINT(1) NOT NULL');
    }

    protected function downMysql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP active');
    }

    public function upSqlite(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_527EDB25727ACA70');
        $this->addSql('DROP INDEX IDX_527EDB25F4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, assigned_to_id, parent_id, name, description, canonical_name, priority, created_at, completed_at, updated_at, due_at, deleted_at, template, time_estimate FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , parent_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, description CLOB NOT NULL, canonical_name VARCHAR(255) NOT NULL, priority INTEGER NOT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, due_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, template BOOLEAN NOT NULL, time_estimate INTEGER DEFAULT NULL, active BOOLEAN NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_527EDB25F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_527EDB25727ACA70 FOREIGN KEY (parent_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO task (id, assigned_to_id, parent_id, name, description, canonical_name, priority, created_at, completed_at, updated_at, due_at, deleted_at, template, time_estimate, active) SELECT id, assigned_to_id, parent_id, name, description, canonical_name, priority, created_at, completed_at, updated_at, due_at, deleted_at, template, time_estimate, false FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25727ACA70 ON task (parent_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25F4BD7827 ON task (assigned_to_id)');
    }

    public function downSqlite(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_527EDB25F4BD7827');
        $this->addSql('DROP INDEX IDX_527EDB25727ACA70');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, assigned_to_id, parent_id, completed_at, name, canonical_name, description, priority, time_estimate, due_at, template, created_at, updated_at, deleted_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , parent_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , completed_at DATETIME DEFAULT NULL, name VARCHAR(255) NOT NULL, canonical_name VARCHAR(255) NOT NULL, description CLOB NOT NULL, priority INTEGER NOT NULL, time_estimate INTEGER DEFAULT NULL, due_at DATETIME DEFAULT NULL, template BOOLEAN NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, deleted_at DATETIME DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO task (id, assigned_to_id, parent_id, completed_at, name, canonical_name, description, priority, time_estimate, due_at, template, created_at, updated_at, deleted_at) SELECT id, assigned_to_id, parent_id, completed_at, name, canonical_name, description, priority, time_estimate, due_at, template, created_at, updated_at, deleted_at FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25F4BD7827 ON task (assigned_to_id)');
        $this->addSql('CREATE INDEX IDX_527EDB25727ACA70 ON task (parent_id)');
    }
}
