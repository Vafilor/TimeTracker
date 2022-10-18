<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\TimeTrackerMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20221018031003 extends TimeTrackerMigration
{
    public function getDescription(): string
    {
        return 'Adds a closedAt timestamp to tasks so you can close them as well as complete';
    }

    public function upPostgresql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task ADD closed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function downPostgresql(Schema $schema): void
    {
        $this->addSql('ALTER TABLE task DROP closed_at');
    }

    protected function upMysql(Schema $schema): void
    {
    }

    protected function downMysql(Schema $schema): void
    {
    }

    public function upSqlite(Schema $schema): void
    {

    }

    public function downSqlite(Schema $schema): void
    {

    }
}
