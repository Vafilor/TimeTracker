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
    }

    protected function downSqlite(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $platformName = $this->platform->getName();
        switch ($platformName)
        {
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
        switch ($platformName)
        {
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
