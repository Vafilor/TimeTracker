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
    public abstract function upPostgresql(Schema $schema): void;
    public abstract function downPostgresql(Schema $schema): void;

    protected abstract function upMysql(Schema $schema) : void;
    protected abstract function downMysql(Schema $schema) : void;

    public abstract function upSqlite(Schema $schema): void;
    public abstract function downSqlite(Schema $schema): void;

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        switch ($platformName) {
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
                throw new Exception("Unsupported database '{$platformName}'");
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        switch ($platformName) {
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
                throw new Exception("Unsupported database '{$platformName}'");
        }
    }
}