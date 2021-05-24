<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210521060151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Updates the database structure for tag associations to use a single table instead of multiple.';
    }

    protected function upPostgresql(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE time_entry_tag_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE timestamp_tag_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE tag_link_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE tag_link (id INT NOT NULL, time_entry_id UUID DEFAULT NULL, timestamp_id UUID DEFAULT NULL, tag_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D8A326471EB30A8E ON tag_link (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_D8A326472F202E84 ON tag_link (timestamp_id)');
        $this->addSql('CREATE INDEX IDX_D8A32647BAD26311 ON tag_link (tag_id)');
        $this->addSql('COMMENT ON COLUMN tag_link.time_entry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN tag_link.timestamp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN tag_link.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326471EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A326472F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A32647BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Copy over old data
        $this->addSql("INSERT INTO tag_link(id, time_entry_id, tag_id) SELECT nextval('tag_link_id_seq'), time_entry_id, tag_id FROM time_entry_tag;");
        $this->addSql("INSERT INTO tag_link(id, timestamp_id, tag_id) SELECT nextval('tag_link_id_seq'), timestamp_id, tag_id FROM timestamp_tag;");

        $this->addSql('DROP TABLE time_entry_tag');
        $this->addSql('DROP TABLE timestamp_tag');
    }

    protected function downPostgresql(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE tag_link_id_seq CASCADE');
        $this->addSql('CREATE SEQUENCE time_entry_tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE timestamp_tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE time_entry_tag (id INT NOT NULL, time_entry_id UUID NOT NULL, tag_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_6243c23dbad26311 ON time_entry_tag (tag_id)');
        $this->addSql('CREATE INDEX idx_6243c23d1eb30a8e ON time_entry_tag (time_entry_id)');
        $this->addSql('COMMENT ON COLUMN time_entry_tag.time_entry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN time_entry_tag.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE timestamp_tag (id INT NOT NULL, tag_id UUID NOT NULL, timestamp_id UUID NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_82a7b8072f202e84 ON timestamp_tag (timestamp_id)');
        $this->addSql('CREATE INDEX idx_82a7b807bad26311 ON timestamp_tag (tag_id)');
        $this->addSql('COMMENT ON COLUMN timestamp_tag.tag_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN timestamp_tag.timestamp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT fk_6243c23d1eb30a8e FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE time_entry_tag ADD CONSTRAINT fk_6243c23dbad26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT fk_82a7b807bad26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE timestamp_tag ADD CONSTRAINT fk_82a7b8072f202e84 FOREIGN KEY (timestamp_id) REFERENCES "timestamp" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Copy over data
        $this->addSql("INSERT INTO time_entry_tag(id, time_entry_id, tag_id) SELECT nextval('time_entry_tag_id_seq'), time_entry_id, tag_id FROM tag_link WHERE time_entry_id IS NOT NULL");
        $this->addSql("INSERT INTO timestamp_tag(id, timestamp_id, tag_id) SELECT nextval('timestamp_tag_id_seq'), timestamp_id, tag_id FROM tag_link WHERE timestamp_id IS NOT NULL");

        $this->addSql('DROP TABLE tag_link');
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
