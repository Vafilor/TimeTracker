<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210614030828 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename createdBy/owner to assignedTo for consistency and because it is not technically the creator of the entity.';
    }

    public function upPostgresql(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE tag DROP CONSTRAINT fk_389b783b03a8386');
        $this->addSql('DROP INDEX idx_389b783b03a8386');
        $this->addSql('ALTER TABLE tag RENAME COLUMN created_by_id TO assigned_to_id');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT FK_389B783F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_389B783F4BD7827 ON tag (assigned_to_id)');

        $this->addSql('ALTER TABLE task DROP CONSTRAINT fk_527edb25b03a8386');
        $this->addSql('DROP INDEX idx_527edb25b03a8386');
        $this->addSql('ALTER TABLE task RENAME COLUMN created_by_id TO assigned_to_id');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_527EDB25F4BD7827 ON task (assigned_to_id)');

        $this->addSql('ALTER TABLE time_entry DROP CONSTRAINT fk_6e537c0c7e3c61f9');
        $this->addSql('DROP INDEX idx_6e537c0c7e3c61f9');
        $this->addSql('ALTER TABLE time_entry RENAME COLUMN owner_id TO assigned_to_id');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT FK_6E537C0CF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_6E537C0CF4BD7827 ON time_entry (assigned_to_id)');

        $this->addSql('ALTER TABLE "timestamp" DROP CONSTRAINT fk_a5d6e63eb03a8386');
        $this->addSql('DROP INDEX idx_a5d6e63eb03a8386');
        $this->addSql('ALTER TABLE "timestamp" RENAME COLUMN created_by_id TO assigned_to_id');
        $this->addSql('ALTER TABLE "timestamp" ADD CONSTRAINT FK_A5D6E63EF4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_A5D6E63EF4BD7827 ON "timestamp" (assigned_to_id)');
    }

    public function downPostgresql(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE task DROP CONSTRAINT FK_527EDB25F4BD7827');
        $this->addSql('DROP INDEX IDX_527EDB25F4BD7827');
        $this->addSql('ALTER TABLE task RENAME COLUMN assigned_to_id TO created_by_id');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT fk_527edb25b03a8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_527edb25b03a8386 ON task (created_by_id)');

        $this->addSql('ALTER TABLE tag DROP CONSTRAINT FK_389B783F4BD7827');
        $this->addSql('DROP INDEX IDX_389B783F4BD7827');
        $this->addSql('ALTER TABLE tag RENAME COLUMN assigned_to_id TO created_by_id');
        $this->addSql('ALTER TABLE tag ADD CONSTRAINT fk_389b783b03a8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_389b783b03a8386 ON tag (created_by_id)');

        $this->addSql('ALTER TABLE time_entry DROP CONSTRAINT FK_6E537C0CF4BD7827');
        $this->addSql('DROP INDEX IDX_6E537C0CF4BD7827');
        $this->addSql('ALTER TABLE time_entry RENAME COLUMN assigned_to_id TO owner_id');
        $this->addSql('ALTER TABLE time_entry ADD CONSTRAINT fk_6e537c0c7e3c61f9 FOREIGN KEY (owner_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_6e537c0c7e3c61f9 ON time_entry (owner_id)');

        $this->addSql('ALTER TABLE timestamp DROP CONSTRAINT FK_A5D6E63EF4BD7827');
        $this->addSql('DROP INDEX IDX_A5D6E63EF4BD7827');
        $this->addSql('ALTER TABLE timestamp RENAME COLUMN assigned_to_id TO created_by_id');
        $this->addSql('ALTER TABLE timestamp ADD CONSTRAINT fk_a5d6e63eb03a8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_a5d6e63eb03a8386 ON timestamp (created_by_id)');
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
