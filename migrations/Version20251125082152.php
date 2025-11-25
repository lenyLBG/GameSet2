<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125082152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipe ADD manual_participants JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE participation_request RENAME INDEX idx_part_tournoi TO IDX_70E93E5EF607770A');
        $this->addSql('ALTER TABLE participation_request RENAME INDEX idx_part_user TO IDX_70E93E5EA76ED395');
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_RENCONTRE_TOURNOI');
        $this->addSql('ALTER TABLE rencontre CHANGE points points INT DEFAULT NULL, CHANGE status status VARCHAR(32) NOT NULL, CHANGE bracket bracket VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_460C35EDF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('ALTER TABLE rencontre RENAME INDEX idx_rencontre_tournoi TO IDX_460C35EDF607770A');
        $this->addSql('ALTER TABLE tournoi RENAME INDEX idx_tournoi_creator TO IDX_18AFD9DF61220EA6');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipe DROP manual_participants');
        $this->addSql('ALTER TABLE tournoi RENAME INDEX idx_18afd9df61220ea6 TO IDX_TOURNOI_CREATOR');
        $this->addSql('ALTER TABLE participation_request RENAME INDEX idx_70e93e5ef607770a TO IDX_PART_TOURNOI');
        $this->addSql('ALTER TABLE participation_request RENAME INDEX idx_70e93e5ea76ed395 TO IDX_PART_USER');
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_460C35EDF607770A');
        $this->addSql('ALTER TABLE rencontre CHANGE points points INT NOT NULL, CHANGE status status VARCHAR(32) DEFAULT \'pending\' NOT NULL, CHANGE bracket bracket VARCHAR(16) DEFAULT \'winners\' NOT NULL');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_RENCONTRE_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rencontre RENAME INDEX idx_460c35edf607770a TO IDX_RENCONTRE_TOURNOI');
    }
}
