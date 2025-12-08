<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251117000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add creator_id to tournoi and FK to user';
    }

    public function up(Schema $schema): void
    {
        // add column
        $this->addSql('ALTER TABLE tournoi ADD creator_id INT DEFAULT NULL');
        // index
        $this->addSql('CREATE INDEX IDX_TOURNOI_CREATOR ON tournoi (creator_id)');
        // foreign key (user table expected to be `user`)
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_TOURNOI_CREATOR FOREIGN KEY (creator_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_TOURNOI_CREATOR');
        $this->addSql('DROP INDEX IDX_TOURNOI_CREATOR ON tournoi');
        $this->addSql('ALTER TABLE tournoi DROP creator_id');
    }
}
