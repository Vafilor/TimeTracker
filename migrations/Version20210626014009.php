<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210626014009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE statistic (id UUID NOT NULL, assigned_to_id UUID NOT NULL, icon VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, canonical_name VARCHAR(255) NOT NULL, description TEXT NOT NULL, color VARCHAR(7) NOT NULL, unit VARCHAR(255) NOT NULL, time_type VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_649B469CF4BD7827 ON statistic (assigned_to_id)');
        $this->addSql('COMMENT ON COLUMN statistic.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN statistic.assigned_to_id IS \'(DC2Type:uuid)\'');
        $this->addSql('CREATE TABLE statistic_value (id UUID NOT NULL, statistic_id UUID NOT NULL, time_entry_id UUID DEFAULT NULL, timestamp_id UUID DEFAULT NULL, value DOUBLE PRECISION NOT NULL, started_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, ended_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_FC7CA2053B6268F ON statistic_value (statistic_id)');
        $this->addSql('CREATE INDEX IDX_FC7CA201EB30A8E ON statistic_value (time_entry_id)');
        $this->addSql('CREATE INDEX IDX_FC7CA202F202E84 ON statistic_value (timestamp_id)');
        $this->addSql('COMMENT ON COLUMN statistic_value.id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN statistic_value.statistic_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN statistic_value.time_entry_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN statistic_value.timestamp_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE statistic ADD CONSTRAINT FK_649B469CF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE statistic_value ADD CONSTRAINT FK_FC7CA2053B6268F FOREIGN KEY (statistic_id) REFERENCES statistic (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE statistic_value ADD CONSTRAINT FK_FC7CA201EB30A8E FOREIGN KEY (time_entry_id) REFERENCES time_entry (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE statistic_value ADD CONSTRAINT FK_FC7CA202F202E84 FOREIGN KEY (timestamp_id) REFERENCES timestamp (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE tag_link ADD statistic_id UUID DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN tag_link.statistic_id IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE tag_link ADD CONSTRAINT FK_D8A3264753B6268F FOREIGN KEY (statistic_id) REFERENCES statistic (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D8A3264753B6268F ON tag_link (statistic_id)');

        $this->addSql('ALTER TABLE users RENAME COLUMN date_format TO date_time_format');
        $this->addSql('ALTER TABLE users RENAME COLUMN today_date_format TO today_date_time_format');
        $this->addSql('ALTER TABLE users ADD date_format VARCHAR(255)');
        $this->addSql("UPDATE users SET date_format = 'm/d/Y'");
        $this->addSql('ALTER TABLE users ALTER COLUMN date_format SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE statistic_value DROP CONSTRAINT FK_FC7CA2053B6268F');
        $this->addSql('ALTER TABLE tag_link DROP CONSTRAINT FK_D8A3264753B6268F');
        $this->addSql('DROP TABLE statistic');
        $this->addSql('DROP TABLE statistic_value');

        $this->addSql('ALTER TABLE users DROP date_format');
        $this->addSql('ALTER TABLE users RENAME COLUMN today_date_time_format TO today_date_format');
        $this->addSql('ALTER TABLE users RENAME COLUMN date_time_format TO date_format');

        $this->addSql('DROP INDEX IDX_D8A3264753B6268F');
        $this->addSql('ALTER TABLE tag_link DROP statistic_id');
    }
}
