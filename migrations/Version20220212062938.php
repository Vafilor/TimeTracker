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

    }

    public function downSqlite(Schema $schema): void
    {

    }
}
