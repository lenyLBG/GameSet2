<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251201100901 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE participation_request (id INT AUTO_INCREMENT NOT NULL, tournoi_id INT NOT NULL, user_id INT NOT NULL, message LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_70E93E5EF607770A (tournoi_id), INDEX IDX_70E93E5EA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participation_request ADD CONSTRAINT FK_70E93E5EF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_request ADD CONSTRAINT FK_70E93E5EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE equipe ADD manual_participants JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE rencontre ADD tournoi_id INT DEFAULT NULL, ADD winner_id INT DEFAULT NULL, ADD position INT DEFAULT NULL, ADD status VARCHAR(32) NOT NULL, ADD bracket VARCHAR(16) NOT NULL, ADD score_home INT DEFAULT NULL, ADD score_away INT DEFAULT NULL, CHANGE points points INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_460C35EDF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_460C35ED5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES equipe (id)');
        $this->addSql('CREATE INDEX IDX_460C35EDF607770A ON rencontre (tournoi_id)');
        $this->addSql('CREATE INDEX IDX_460C35ED5DFCD4B8 ON rencontre (winner_id)');
        $this->addSql('ALTER TABLE tournoi ADD creator_id INT DEFAULT NULL, ADD winner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_18AFD9DF61220EA6 FOREIGN KEY (creator_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_18AFD9DF5DFCD4B8 FOREIGN KEY (winner_id) REFERENCES equipe (id)');
        $this->addSql('CREATE INDEX IDX_18AFD9DF61220EA6 ON tournoi (creator_id)');
        $this->addSql('CREATE INDEX IDX_18AFD9DF5DFCD4B8 ON tournoi (winner_id)');
        $this->addSql('ALTER TABLE user ADD avatar VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE participation_request DROP FOREIGN KEY FK_70E93E5EF607770A');
        $this->addSql('ALTER TABLE participation_request DROP FOREIGN KEY FK_70E93E5EA76ED395');
        $this->addSql('DROP TABLE participation_request');
        $this->addSql('ALTER TABLE user DROP avatar');
        $this->addSql('ALTER TABLE equipe DROP manual_participants');
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_18AFD9DF61220EA6');
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_18AFD9DF5DFCD4B8');
        $this->addSql('DROP INDEX IDX_18AFD9DF61220EA6 ON tournoi');
        $this->addSql('DROP INDEX IDX_18AFD9DF5DFCD4B8 ON tournoi');
        $this->addSql('ALTER TABLE tournoi DROP creator_id, DROP winner_id');
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_460C35EDF607770A');
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_460C35ED5DFCD4B8');
        $this->addSql('DROP INDEX IDX_460C35EDF607770A ON rencontre');
        $this->addSql('DROP INDEX IDX_460C35ED5DFCD4B8 ON rencontre');
        $this->addSql('ALTER TABLE rencontre DROP tournoi_id, DROP winner_id, DROP position, DROP status, DROP bracket, DROP score_home, DROP score_away, CHANGE points points INT NOT NULL');
    }
}
