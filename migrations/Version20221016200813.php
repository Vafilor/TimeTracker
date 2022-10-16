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
        $this->addSql('ALTER TABLE task ADD active BOOLEAN NOT NULL');
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP active');
    }

    protected function upMysql(Schema $schema): void
    {
        // TODO
    }

    protected function downMysql(Schema $schema): void
    {
        // TODO
    }

    public function upSqlite(Schema $schema): void
    {
        // TODO
    }

    public function downSqlite(Schema $schema): void
    {
        // TODO
    }
}
