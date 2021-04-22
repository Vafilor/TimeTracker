<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422213639 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds a started at column to distinguish between when a time entry was created vs the start time it tracks.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE time_entry ADD started_at TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('UPDATE time_entry SET started_at = created_at');
        $this->addSql('ALTER TABLE time_entry ALTER COLUMN started_at SET NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE time_entry DROP started_at');
    }
}
