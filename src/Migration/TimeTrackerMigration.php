<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
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
        if ($this->platform instanceof PostgreSQLPlatform) {
            $this->upPostgresql($schema);
        } else if($this->platform instanceof AbstractMySQLPlatform) {
            $this->upMysql($schema);
        } else if($this->platform instanceof SQLitePlatform) {
            $this->upSqlite($schema);
        } else {
            throw new Exception("Unsupported database platform");
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->platform instanceof PostgreSQLPlatform) {
            $this->downPostgresql($schema);
        } else if($this->platform instanceof AbstractMySQLPlatform) {
            $this->downMysql($schema);
        } else if($this->platform instanceof SQLitePlatform) {
            $this->downSqlite($schema);
        } else {
            throw new Exception("Unsupported database platform");
        }
    }
}
