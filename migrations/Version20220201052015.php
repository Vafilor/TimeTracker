<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\TimeTrackerMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220201052015 extends TimeTrackerMigration
{
    public function getDescription(): string
    {
        return 'Adds description to timestamp.';
    }

    public function upPostgresql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "timestamp" ADD description TEXT');
        $this->addSql("UPDATE \"timestamp\" SET description = ''");
        $this->addSql('ALTER TABLE "timestamp" ALTER COLUMN description SET NOT NULL');
    }

    public function downPostgresql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "timestamp" DROP description');
    }

    protected function upMysql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE timestamp ADD description LONGTEXT');
        $this->addSql("UPDATE timestamp SET description = ''");
        $this->addSql('ALTER TABLE timestamp CHANGE description description LONGTEXT NOT NULL');
    }

    protected function downMysql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE timestamp DROP description');
    }

    public function upSqlite(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_A5D6E63EF4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, assigned_to_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, description CLOB NOT NULL, PRIMARY KEY(id), CONSTRAINT FK_A5D6E63EF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO timestamp (id, assigned_to_id, created_at, description) SELECT id, assigned_to_id, created_at, \'\' FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EF4BD7827 ON timestamp (assigned_to_id)');
    }

    public function downSqlite(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_A5D6E63EF4BD7827');
        $this->addSql('CREATE TEMPORARY TABLE __temp__timestamp AS SELECT id, assigned_to_id, created_at FROM timestamp');
        $this->addSql('DROP TABLE timestamp');
        $this->addSql('CREATE TABLE timestamp (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , created_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO timestamp (id, assigned_to_id, created_at) SELECT id, assigned_to_id, created_at FROM __temp__timestamp');
        $this->addSql('DROP TABLE __temp__timestamp');
        $this->addSql('CREATE INDEX IDX_A5D6E63EF4BD7827 ON timestamp (assigned_to_id)');
    }
}
