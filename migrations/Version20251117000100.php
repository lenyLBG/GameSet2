<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251117000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Populate existing tournoi.creator_id with first user id (if any)';
    }

    public function up(Schema $schema): void
    {
        // Set creator_id to the first user id available for existing tournois that have NULL creator
        // This is defensive: if no user exists, the subquery returns NULL and nothing changes.
        $this->addSql("UPDATE tournoi SET creator_id = (
            SELECT id FROM `user` ORDER BY id LIMIT 1
        ) WHERE creator_id IS NULL");
    }

    public function down(Schema $schema): void
    {
        // Revert: clear creator_id for all tournois
        $this->addSql('UPDATE tournoi SET creator_id = NULL');
    }
}
