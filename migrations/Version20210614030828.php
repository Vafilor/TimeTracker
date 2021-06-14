<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210614030828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename createdBy/owner to assignedTo for consistency and because it is not technically the creator of the entity.';
    }

    public function upPostgresql(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag DROP CONSTRAINT fk_389b783b03a8386');
        $this->addSql('DROP INDEX idx_389b783b03a8386');
        $this->addSql('ALTER TABLE tag RENAME COLUMN created_by_id TO assigned_to_id');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B783F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_389B783F4BD7827 ON tag (assigned_to_id)');

        $this->addSql('ALTER TABLE task DROP CONSTRAINT fk_527edb25b03a8386');
        $this->addSql('DROP INDEX idx_527edb25b03a8386');
        $this->addSql('ALTER TABLE task RENAME COLUMN created_by_id TO assigned_to_id');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_527EDB25F4BD7827 ON task (assigned_to_id)');

        $this->addSql('ALTER TABLE time_entry DROP CONSTRAINT fk_6e537c0c7e3c61f9');
        $this->addSql('DROP INDEX idx_6e537c0c7e3c61f9');
        $this->addSql('ALTER TABLE time_entry RENAME COLUMN owner_id TO assigned_to_id');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0CF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6E537C0CF4BD7827 ON time_entry (assigned_to_id)');

        $this->addSql('ALTER TABLE "timestamp" DROP CONSTRAINT fk_a5d6e63eb03a8386');
        $this->addSql('DROP INDEX idx_a5d6e63eb03a8386');
        $this->addSql('ALTER TABLE "timestamp" RENAME COLUMN created_by_id TO assigned_to_id');
        $this->addSql('ALTER TABLE "timestamp" ADD CONSTRAINT FK_A5D6E63EF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A5D6E63EF4BD7827 ON "timestamp" (assigned_to_id)');
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25F4BD7827');
        $this->addSql('DROP INDEX IDX_527EDB25F4BD7827');
        $this->addSql('ALTER TABLE task RENAME COLUMN assigned_to_id TO created_by_id');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT fk_527edb25b03a8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_527edb25b03a8386 ON task (created_by_id)');

        $this->addSql('ALTER TABLE tag DROP CONSTRAINT FK_389B783F4BD7827');
        $this->addSql('DROP INDEX IDX_389B783F4BD7827');
        $this->addSql('ALTER TABLE tag RENAME COLUMN assigned_to_id TO created_by_id');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT fk_389b783b03a8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_389b783b03a8386 ON tag (created_by_id)');

        $this->addSql('ALTER TABLE time_entry DROP CONSTRAINT FK_6E537C0CF4BD7827');
        $this->addSql('DROP INDEX IDX_6E537C0CF4BD7827');
        $this->addSql('ALTER TABLE time_entry RENAME COLUMN assigned_to_id TO owner_id');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT fk_6e537c0c7e3c61f9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_6e537c0c7e3c61f9 ON time_entry (owner_id)');

        $this->addSql('ALTER TABLE timestamp DROP CONSTRAINT FK_A5D6E63EF4BD7827');
        $this->addSql('DROP INDEX IDX_A5D6E63EF4BD7827');
        $this->addSql('ALTER TABLE timestamp RENAME COLUMN assigned_to_id TO created_by_id');
        $this->addSql('ALTER TABLE timestamp ADD CONSTRAINT fk_a5d6e63eb03a8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_a5d6e63eb03a8386 ON timestamp (created_by_id)');
    }

    protected function upMysql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B783B03A8386');
        $this->addSql('DROP INDEX IDX_389B783B03A8386 ON tag');
        $this->addSql('ALTER TABLE tag CHANGE created_by_id assigned_to_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B783F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_389B783F4BD7827 ON tag (assigned_to_id)');

        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25B03A8386');
        $this->addSql('DROP INDEX IDX_527EDB25B03A8386 ON task');
        $this->addSql('ALTER TABLE task CHANGE created_by_id assigned_to_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_527EDB25F4BD7827 ON task (assigned_to_id)')
        ;
        $this->addSql('ALTER TABLE time_entry DROP FOREIGN KEY FK_6E537C0C7E3C61F9');
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9 ON time_entry');
        $this->addSql('ALTER TABLE time_entry CHANGE owner_id assigned_to_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0CF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_6E537C0CF4BD7827 ON time_entry (assigned_to_id)');

        $this->addSql('ALTER TABLE timestamp DROP FOREIGN KEY FK_A5D6E63EB03A8386');
        $this->addSql('DROP INDEX IDX_A5D6E63EB03A8386 ON timestamp');
        $this->addSql('ALTER TABLE timestamp CHANGE created_by_id assigned_to_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE timestamp ADD CONSTRAINT FK_A5D6E63EF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_A5D6E63EF4BD7827 ON timestamp (assigned_to_id)');
    }

    protected function downMysql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag DROP FOREIGN KEY FK_389B783F4BD7827');
        $this->addSql('DROP INDEX IDX_389B783F4BD7827 ON tag');
        $this->addSql('ALTER TABLE tag CHANGE assigned_to_id created_by_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B783B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)');

        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25F4BD7827');
        $this->addSql('DROP INDEX IDX_527EDB25F4BD7827 ON task');
        $this->addSql('ALTER TABLE task CHANGE assigned_to_id created_by_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_527EDB25B03A8386 ON task (created_by_id)');

        $this->addSql('ALTER TABLE time_entry DROP FOREIGN KEY FK_6E537C0CF4BD7827');
        $this->addSql('DROP INDEX IDX_6E537C0CF4BD7827 ON time_entry');
        $this->addSql('ALTER TABLE time_entry CHANGE assigned_to_id owner_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');

        $this->addSql('ALTER TABLE timestamp DROP FOREIGN KEY FK_A5D6E63EF4BD7827');
        $this->addSql('DROP INDEX IDX_A5D6E63EF4BD7827 ON timestamp');
        $this->addSql('ALTER TABLE timestamp CHANGE assigned_to_id created_by_id CHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE timestamp ADD CONSTRAINT FK_A5D6E63EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
    }

    protected function upSqlite(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_389B783B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, created_by_id, name, color, created_at, canonical_name FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL COLLATE BINARY, color VARCHAR(7) NOT NULL COLLATE BINARY, canonical_name VARCHAR(255) NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_389B783F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO tag (id, assigned_to_id, name, color, created_at, canonical_name) SELECT id, created_by_id, name, color, created_at, canonical_name FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
        $this->addSql('CREATE INDEX IDX_389B783F4BD7827 ON tag (assigned_to_id)');

        $this->addSql('DROP INDEX IDX_527EDB25B03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, created_by_id, name, description, created_at, completed_at, updated_at, canonical_name, priority FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL COLLATE BINARY, description CLOB NOT NULL COLLATE BINARY, canonical_name VARCHAR(255) NOT NULL COLLATE BINARY, priority INTEGER NOT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_527EDB25F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO task (id, assigned_to_id, name, description, created_at, completed_at, updated_at, canonical_name, priority) SELECT id, created_by_id, name, description, created_at, completed_at, updated_at, canonical_name, priority FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25F4BD7827 ON task (assigned_to_id)');

        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('DROP INDEX IDX_6E537C0C7E3C61F9');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, owner_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , description CLOB NOT NULL COLLATE BINARY, created_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, started_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_6E537C0CF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_6E537C0C8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO time_entry (id, assigned_to_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at) SELECT id, owner_id, task_id, description, created_at, ended_at, deleted_at, started_at, updated_at FROM __temp__time_entry');
        $this->addSql('DROP TABLE __temp__time_entry');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
        $this->addSql('CREATE INDEX IDX_6E537C0CF4BD7827 ON time_entry (assigned_to_id)');

        $this->addSql('DROP INDEX IDX_A5D6E63EB03A8386');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, created_by_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_A5D6E63EF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO timestamp (id, assigned_to_id, created_at) SELECT id, created_by_id, created_at FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EF4BD7827 ON timestamp (assigned_to_id)');
    }

    protected function downSqlite(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_389B783F4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag AS SELECT id, assigned_to_id, name, canonical_name, color, created_at FROM tag');
        $this->addSql('DROP TABLE tag');
        $this->addSql('CREATE TABLE tag (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, canonical_name VARCHAR(255) NOT NULL, color VARCHAR(7) NOT NULL, created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO tag (id, created_by_id, name, canonical_name, color, created_at) SELECT id, assigned_to_id, name, canonical_name, color, created_at FROM __temp__tag');
        $this->addSql('DROP TABLE __temp__tag');
        $this->addSql('CREATE INDEX IDX_389B783B03A8386 ON tag (created_by_id)');

        $this->addSql('DROP INDEX IDX_527EDB25F4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__task AS SELECT id, assigned_to_id, completed_at, name, canonical_name, description, priority, created_at, updated_at FROM task');
        $this->addSql('DROP TABLE task');
        $this->addSql('CREATE TABLE task (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , name VARCHAR(255) NOT NULL, canonical_name VARCHAR(255) NOT NULL, description CLOB NOT NULL, priority INTEGER NOT NULL, created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , completed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO task (id, created_by_id, completed_at, name, canonical_name, description, priority, created_at, updated_at) SELECT id, assigned_to_id, completed_at, name, canonical_name, description, priority, created_at, updated_at FROM __temp__task');
        $this->addSql('DROP TABLE __temp__task');
        $this->addSql('CREATE INDEX IDX_527EDB25B03A8386 ON task (created_by_id)');

        $this->addSql('DROP INDEX IDX_6E537C0CF4BD7827');
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('CREATE TEMPORARY TABLE __temp__time_entry AS SELECT id, assigned_to_id, task_id, started_at, ended_at, deleted_at, description, created_at, updated_at FROM time_entry');
        $this->addSql('DROP TABLE time_entry');
        $this->addSql('CREATE TABLE time_entry (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , description CLOB NOT NULL, owner_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , started_at DATETIME NOT NULL, ended_at DATETIME DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO time_entry (id, owner_id, task_id, started_at, ended_at, deleted_at, description, created_at, updated_at) SELECT id, assigned_to_id, task_id, started_at, ended_at, deleted_at, description, created_at, updated_at FROM __temp__time_entry');
        $this->addSql('DROP TABLE __temp__time_entry');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
        $this->addSql('CREATE INDEX IDX_6E537C0C7E3C61F9 ON time_entry (owner_id)');

        $this->addSql('DROP INDEX IDX_A5D6E63EF4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, assigned_to_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_by_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO timestamp (id, created_by_id, created_at) SELECT id, assigned_to_id, created_at FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EB03A8386 ON timestamp (created_by_id)');
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
