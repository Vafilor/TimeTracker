<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210701185233 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update databases to use datetime instead of datetime timezone. Only affects postgres.';
    }

    public function upPostgresql(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        // Regarding SELECT age(now() at time zone 'UTC', now())
        // this will return an interval of how far away UTC is from the database server's timezone
        // once we remove the timezone offset, we can add this to the value to adjust it to be UTC time.

        $this->addSql('ALTER TABLE tag ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE tag ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE tag SET created_at = created_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE task ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE task ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE task SET created_at = created_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE task ALTER completed_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE task ALTER completed_at DROP DEFAULT');
        $this->addSql("UPDATE task SET completed_at = completed_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE task ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE task ALTER updated_at DROP DEFAULT');
        $this->addSql("UPDATE task SET updated_at = updated_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE time_entry ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET created_at = created_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE time_entry ALTER ended_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER ended_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET ended_at = ended_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE time_entry ALTER deleted_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER deleted_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET deleted_at = deleted_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE time_entry ALTER updated_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER updated_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET updated_at = updated_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE time_entry ALTER started_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER started_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET started_at = started_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE "timestamp" ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE "timestamp" ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE \"timestamp\" SET created_at = created_at + (SELECT age(now() at time zone 'UTC', now()))");

        $this->addSql('ALTER TABLE users ALTER created_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        $this->addSql('ALTER TABLE users ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE users SET created_at = created_at + (SELECT age(now() at time zone 'UTC', now()))");
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        // Regarding SELECT age(now(), now() at time zone 'UTC')
        // this will return an interval of how far away the database server's timezone is from UTC
        // once we add the timezone offset back, we can add this to the value to adjust it to be local time with the appropriate offset

        $this->addSql('ALTER TABLE tag ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE tag ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE tag SET created_at = created_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE time_entry ALTER started_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER started_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET started_at = started_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE time_entry ALTER ended_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER ended_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET ended_at = ended_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE time_entry ALTER deleted_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER deleted_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET deleted_at = deleted_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE time_entry ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET created_at = created_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE time_entry ALTER updated_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE time_entry ALTER updated_at DROP DEFAULT');
        $this->addSql("UPDATE time_entry SET updated_at = updated_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE timestamp ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE timestamp ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE timestamp SET created_at = created_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE task ALTER completed_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE task ALTER completed_at DROP DEFAULT');
        $this->addSql("UPDATE task SET completed_at = completed_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE task ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE task ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE task SET created_at = created_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE task ALTER updated_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE task ALTER updated_at DROP DEFAULT');
        $this->addSql("UPDATE task SET updated_at = updated_at + (SELECT age(now(), now() at time zone 'UTC'))");

        $this->addSql('ALTER TABLE users ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE users ALTER created_at DROP DEFAULT');
        $this->addSql("UPDATE users SET created_at = created_at + (SELECT age(now(), now() at time zone 'UTC'))");
    }

    protected function upMysql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // do nothing
    }

    protected function downMysql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // do nothing
    }

    protected function upSqlite(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // do nothing
    }

    protected function downSqlite(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // do nothing
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
