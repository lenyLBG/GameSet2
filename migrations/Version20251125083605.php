<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125083605 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rencontre ADD winner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_460C35ED5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES equipe (id)');
        $this->addSql('CREATE INDEX IDX_460C35ED5DFCD4B8 ON rencontre (winner_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_460C35ED5DFCD4B8');
        $this->addSql('DROP INDEX IDX_460C35ED5DFCD4B8 ON rencontre');
        $this->addSql('ALTER TABLE rencontre DROP winner_id');
    }
}
