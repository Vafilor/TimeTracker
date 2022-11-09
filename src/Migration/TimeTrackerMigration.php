<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;

/**
 * TimeTrackerMigration indicates a migration specifically for this project that
 * requires migrations for Postgresql, Mysql, and Sqlite.
 */
abstract class TimeTrackerMigration extends AbstractMigration
{
    abstract public function upPostgresql(Schema $schema): void;

    abstract public function downPostgresql(Schema $schema): void;

    abstract protected function upMysql(Schema $schema): void;

    abstract protected function downMysql(Schema $schema): void;

    abstract public function upSqlite(Schema $schema): void;

    abstract public function downSqlite(Schema $schema): void;

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        match ($platformName) {
            'sqlite' => $this->upSqlite($schema),
            'postgresql' => $this->upPostgresql($schema),
            'mysql' => $this->upMysql($schema),
            default => throw new Exception("Unsupported database '{$platformName}'"),
        };
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        match ($platformName) {
            'sqlite' => $this->downSqlite($schema),
            'postgresql' => $this->downPostgresql($schema),
            'mysql' => $this->downMysql($schema),
            default => throw new Exception("Unsupported database '{$platformName}'"),
        };
    }
}
