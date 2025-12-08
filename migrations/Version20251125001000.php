<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251125001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add bracket column to rencontre (winners or losers)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE rencontre ADD bracket VARCHAR(16) NOT NULL DEFAULT 'winners'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE rencontre DROP bracket');
    }
}
