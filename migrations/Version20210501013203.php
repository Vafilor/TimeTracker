<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210501013203 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Creates a task table and associates it to time entries';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        $this->addSql('CREATE TABLE task (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN task.id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE time_entry ADD task_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN time_entry.task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0C8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6E537C0C8DB60186 ON time_entry (task_id)');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE time_entry DROP CONSTRAINT FK_6E537C0C8DB60186');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP INDEX IDX_6E537C0C8DB60186');
        $this->addSql('ALTER TABLE time_entry DROP task_id');
    }

    protected function upMysql(Schema $schema) : void
    {
    }

    protected function downMysql(Schema $schema) : void
    {
    }

    protected function upSqlite(Schema $schema) : void
    {

    }

    protected function downSqlite(Schema $schema) : void
    {
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
