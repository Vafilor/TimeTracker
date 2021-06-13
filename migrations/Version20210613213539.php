<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210613213539 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates Tasks to have a priority, canonical name, and tags.';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag_link ADD task_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN tag_link.task_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326478DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8A326478DB60186 ON tag_link (task_id)');

        $this->addSql('ALTER TABLE task ADD canonical_name VARCHAR(255)');
        $this->addSql('UPDATE task SET canonical_name = LOWER(name)');
        $this->addSql('ALTER TABLE task ALTER COLUMN canonical_name SET NOT NULL');

        $this->addSql('ALTER TABLE task ADD priority INT');
        $this->addSql('UPDATE task SET priority = 0');
        $this->addSql('ALTER TABLE task ALTER COLUMN priority SET NOT NULL');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP canonical_name');
        $this->addSql('ALTER TABLE task DROP priority');
        $this->addSql('ALTER TABLE tag_link DROP CONSTRAINT FK_D8A326478DB60186');
        $this->addSql('DROP INDEX IDX_D8A326478DB60186');
        $this->addSql('ALTER TABLE tag_link DROP task_id');
    }

    protected function upMysql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
    }

    protected function downMysql(Schema $schema) : void
    {
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
