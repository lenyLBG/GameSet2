<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251125000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add tournoi_id, position, status, score_home and score_away to rencontre table and FK to tournoi';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rencontre ADD tournoi_id INT DEFAULT NULL, ADD position INT DEFAULT NULL, ADD status VARCHAR(32) NOT NULL DEFAULT \'' . 'pending' . '\', ADD score_home INT DEFAULT NULL, ADD score_away INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_RENCONTRE_TOURNOI ON rencontre (tournoi_id)');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_RENCONTRE_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_RENCONTRE_TOURNOI');
        $this->addSql('DROP INDEX IDX_RENCONTRE_TOURNOI ON rencontre');
        $this->addSql('ALTER TABLE rencontre DROP tournoi_id, DROP position, DROP status, DROP score_home, DROP score_away');
    }
}
