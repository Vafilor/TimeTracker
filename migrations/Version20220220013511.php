<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Migration\TimeTrackerMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220220013511 extends TimeTrackerMigration
{
    public function getDescription(): string
    {
        return 'Adds an optional time estimate to tasks.';
    }

    public function upPostgresql(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task ADD time_estimate INTEGER DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN task.time_estimate IS \'(DC2Type:dateinterval)\'');
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP time_estimate');
    }

    protected function upMysql(Schema $schema): void
    {
        // TODO: Implement upMysql() method.
    }

    protected function downMysql(Schema $schema): void
    {
        // TODO: Implement downMysql() method.
    }

    public function upSqlite(Schema $schema): void
    {
        // TODO: Implement upSqlite() method.
    }

    public function downSqlite(Schema $schema): void
    {
        // TODO: Implement downSqlite() method.
    }
}
