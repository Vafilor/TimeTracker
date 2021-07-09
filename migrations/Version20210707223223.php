<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Exception;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210707223223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates notes resources';
    }

    public function upPostgresql(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE note (id UUID NOT NULL, assigned_to_id UUID NOT NULL, title VARCHAR(255) NOT NULL, content TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CFBDFA14F4BD7827 ON note (assigned_to_id)');
        $this->addSql('COMMENT ON COLUMN note.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN note.assigned_to_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_link ADD note_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN tag_link.note_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A3264726ED0855 FOREIGN KEY (note_id) REFERENCES note (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8A3264726ED0855 ON tag_link (note_id)');
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag_link DROP CONSTRAINT FK_D8A3264726ED0855');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP INDEX IDX_D8A3264726ED0855');
        $this->addSql('ALTER TABLE tag_link DROP note_id');
    }

    protected function upMysql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE note (id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', assigned_to_id CHAR(36) NOT NULL COMMENT \'(DC2Type:uuid)\', title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_CFBDFA14F4BD7827 (assigned_to_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE note ADD CONSTRAINT FK_CFBDFA14F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id)');
        $this->addSql('ALTER TABLE tag_link ADD note_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A3264726ED0855 FOREIGN KEY (note_id) REFERENCES note (id)');
        $this->addSql('CREATE INDEX IDX_D8A3264726ED0855 ON tag_link (note_id)');
    }

    protected function downMysql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag_link DROP FOREIGN KEY FK_D8A3264726ED0855');
        $this->addSql('DROP TABLE note');
        $this->addSql('DROP INDEX IDX_D8A3264726ED0855 ON tag_link');
        $this->addSql('ALTER TABLE tag_link DROP note_id');
    }

    protected function upSqlite(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE note (id CHAR(36) NOT NULL --(DC2Type:uuid)
        , assigned_to_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , title VARCHAR(255) NOT NULL, content CLOB NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_CFBDFA14F4BD7827 ON note (assigned_to_id)');

        $this->addSql('DROP INDEX IDX_D8A3264753B6268F');
        $this->addSql('DROP INDEX IDX_D8A32647BAD26311');
        $this->addSql('DROP INDEX IDX_D8A326472F202E84');
        $this->addSql('DROP INDEX IDX_D8A326471EB30A8E');
        $this->addSql('DROP INDEX IDX_D8A326478DB60186');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag_link AS SELECT id, time_entry_id, timestamp_id, tag_id, task_id, statistic_id FROM tag_link');
        $this->addSql('DROP TABLE tag_link');
        $this->addSql('CREATE TABLE tag_link (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , timestamp_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL COLLATE BINARY --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , statistic_id CHAR(36) DEFAULT NULL COLLATE BINARY --(DC2Type:uuid)
        , note_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , CONSTRAINT FK_D8A326471EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8A326472F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8A326478DB60186 FOREIGN KEY (task_id) REFERENCES task (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8A3264726ED0855 FOREIGN KEY (note_id) REFERENCES note (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8A32647BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_D8A3264753B6268F FOREIGN KEY (statistic_id) REFERENCES statistic (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO tag_link (id, time_entry_id, timestamp_id, tag_id, task_id, statistic_id) SELECT id, time_entry_id, timestamp_id, tag_id, task_id, statistic_id FROM __temp__tag_link');
        $this->addSql('DROP TABLE __temp__tag_link');
        $this->addSql('CREATE INDEX IDX_D8A3264753B6268F ON tag_link (statistic_id)');
        $this->addSql('CREATE INDEX IDX_D8A32647BAD26311 ON tag_link (tag_id)');
        $this->addSql('CREATE INDEX IDX_D8A326472F202E84 ON tag_link (timestamp_id)');
        $this->addSql('CREATE INDEX IDX_D8A326471EB30A8E ON tag_link (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_D8A326478DB60186 ON tag_link (task_id)');
        $this->addSql('CREATE INDEX IDX_D8A3264726ED0855 ON tag_link (note_id)');
    }

    protected function downSqlite(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE note');

        $this->addSql('DROP INDEX IDX_D8A326471EB30A8E');
        $this->addSql('DROP INDEX IDX_D8A326472F202E84');
        $this->addSql('DROP INDEX IDX_D8A326478DB60186');
        $this->addSql('DROP INDEX IDX_D8A3264726ED0855');
        $this->addSql('DROP INDEX IDX_D8A32647BAD26311');
        $this->addSql('DROP INDEX IDX_D8A3264753B6268F');
        $this->addSql('CREATE TEMPORARY TABLE __temp__tag_link AS SELECT id, time_entry_id, timestamp_id, task_id, tag_id, statistic_id FROM tag_link');
        $this->addSql('DROP TABLE tag_link');
        $this->addSql('CREATE TABLE tag_link (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, time_entry_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , timestamp_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , task_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        , tag_id CHAR(36) NOT NULL --(DC2Type:uuid)
        , statistic_id CHAR(36) DEFAULT NULL --(DC2Type:uuid)
        )');
        $this->addSql('INSERT INTO tag_link (id, time_entry_id, timestamp_id, task_id, tag_id, statistic_id) SELECT id, time_entry_id, timestamp_id, task_id, tag_id, statistic_id FROM __temp__tag_link');
        $this->addSql('DROP TABLE __temp__tag_link');
        $this->addSql('CREATE INDEX IDX_D8A326471EB30A8E ON tag_link (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_D8A326472F202E84 ON tag_link (timestamp_id)');
        $this->addSql('CREATE INDEX IDX_D8A326478DB60186 ON tag_link (task_id)');
        $this->addSql('CREATE INDEX IDX_D8A32647BAD26311 ON tag_link (tag_id)');
        $this->addSql('CREATE INDEX IDX_D8A3264753B6268F ON tag_link (statistic_id)');
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
