<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\TimeTrackerMigration;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220212062938 extends TimeTrackerMigration
{
    public function getDescription(): string
    {
        return 'Adds for_date to Notes to indicate what date the note is for.';
    }

    public function upPostgresql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note ADD for_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function downPostgresql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE note DROP for_date');
    }

    protected function upMysql(Schema $schema) : void
    {

    }

    protected function downMysql(Schema $schema) : void
    {
    }

    public function upSqlite(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_CFBDFA14F4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__note AS SELECT id, assigned_to_id, title, content, created_at, updated_at FROM note');
        $this->addSql('DROP TABLE note');
        $this->addSql('CREATE TABLE note (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , title VARCHAR(255) NOT NULL, content CLOB NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, for_date DATETIME DEFAULT NULL, PRIMARY KEY(id), CONSTRAINT FK_CFBDFA14F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO note (id, assigned_to_id, title, content, created_at, updated_at) SELECT id, assigned_to_id, title, content, created_at, updated_at FROM __temp__note');
        $this->addSql('DROP TABLE __temp__note');
        $this->addSql('CREATE INDEX IDX_CFBDFA14F4BD7827 ON note (assigned_to_id)');
    }

    public function downSqlite(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_CFBDFA14F4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__note AS SELECT id, assigned_to_id, title, content, created_at, updated_at FROM note');
        $this->addSql('DROP TABLE note');
        $this->addSql('CREATE TABLE note (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , title VARCHAR(255) NOT NULL, content CLOB NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO note (id, assigned_to_id, title, content, created_at, updated_at) SELECT id, assigned_to_id, title, content, created_at, updated_at FROM __temp__note');
        $this->addSql('DROP TABLE __temp__note');
        $this->addSql('CREATE INDEX IDX_CFBDFA14F4BD7827 ON note (assigned_to_id)');
    }
}
