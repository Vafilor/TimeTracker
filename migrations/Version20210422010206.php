<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210422010206 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Adds date and duration formats to user as settings.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD date_format VARCHAR(255)');
        $this->addSql("UPDATE users SET date_format = 'h:i:s A'");
        $this->addSql('ALTER TABLE users ALTER COLUMN date_format SET NOT NULL');

        $this->addSql('ALTER TABLE users ADD duration_format VARCHAR(255)');
        $this->addSql("UPDATE users SET duration_format = '%hh %Im %Ss'");
        $this->addSql('ALTER TABLE users ALTER COLUMN duration_format SET NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP date_format');
        $this->addSql('ALTER TABLE users DROP duration_format');
    }
}
