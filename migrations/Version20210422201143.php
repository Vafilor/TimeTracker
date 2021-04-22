<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422201143 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds a updated_at column to time entries.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE time_entry ADD updated_at TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('UPDATE time_entry SET updated_at = created_at');
        $this->addSql('ALTER TABLE time_entry ALTER COLUMN updated_at SET NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE time_entry DROP updated_at');
    }
}
