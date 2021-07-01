<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210701160848 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update users to have a date format, datetime format, and today_datetime format preference.';
    }

    public function upPostgresql(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users RENAME COLUMN date_format TO date_time_format');
        $this->addSql('ALTER TABLE users RENAME COLUMN today_date_format TO today_date_time_format');
        $this->addSql('ALTER TABLE users ADD date_format VARCHAR(255)');
        $this->addSql("UPDATE users SET date_format = 'm/d/Y'");
        $this->addSql('ALTER TABLE users ALTER COLUMN date_format SET NOT NULL');
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP date_format');
        $this->addSql('ALTER TABLE users RENAME COLUMN today_date_time_format TO today_date_format');
        $this->addSql('ALTER TABLE users RENAME COLUMN date_time_format TO date_format');
    }

    protected function upMysql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
    }

    protected function downMysql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    protected function upSqlite(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL COLLATE BINARY, date_format VARCHAR(255) NOT NULL COLLATE BINARY, duration_format VARCHAR(255) NOT NULL COLLATE BINARY, email VARCHAR(180) NOT NULL COLLATE BINARY, username VARCHAR(180) NOT NULL COLLATE BINARY, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL COLLATE BINARY, roles CLOB NOT NULL COLLATE BINARY --(DC2Type:json)
        , password VARCHAR(255) NOT NULL COLLATE BINARY, date_time_format VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, today_date_time_format VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, password_requested_at, is_verified, timezone, date_format, date_time_format, today_date_time_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at) SELECT id, password_requested_at, is_verified, timezone, "m/d/y", date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
    }

    protected function downSqlite(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74');
        $this->addSql('DROP INDEX UNIQ_1483A5E9F85E0677');
        $this->addSql('CREATE TEMPORARY TABLE __temp__users AS SELECT id, password_requested_at, is_verified, timezone, date_time_format, today_date_time_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM users');
        $this->addSql('DROP TABLE users');
        $this->addSql('CREATE TABLE users (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , password_requested_at DATETIME DEFAULT NULL, is_verified BOOLEAN NOT NULL, timezone VARCHAR(255) NOT NULL, date_format VARCHAR(255) NOT NULL, duration_format VARCHAR(255) NOT NULL, email VARCHAR(180) NOT NULL, username VARCHAR(180) NOT NULL, enabled BOOLEAN NOT NULL, confirmation_token VARCHAR(180) DEFAULT NULL, roles CLOB NOT NULL --(DC2Type:json)
        , password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, today_date_format VARCHAR(255) NOT NULL COLLATE BINARY, PRIMARY KEY(id))');
        $this->addSql('INSERT INTO users (id, password_requested_at, is_verified, timezone, date_format, today_date_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at) SELECT id, password_requested_at, is_verified, timezone, date_time_format, today_date_time_format, duration_format, email, username, enabled, confirmation_token, roles, password, created_at FROM __temp__users');
        $this->addSql('DROP TABLE __temp__users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9F85E0677 ON users (username)');
    }

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
                throw new \Exception("Unsupported database '{$platformName}'");
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
                throw new \Exception("Unsupported database '{$platformName}'");
        }
    }
}
