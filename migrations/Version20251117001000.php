<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251117001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create participation_request table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE participation_request (id INT AUTO_INCREMENT NOT NULL, tournoi_id INT NOT NULL, user_id INT NOT NULL, message LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_PART_TOURNOI (tournoi_id), INDEX IDX_PART_USER (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participation_request ADD CONSTRAINT FK_PART_TOURNOI FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE participation_request ADD CONSTRAINT FK_PART_USER FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participation_request DROP FOREIGN KEY FK_PART_TOURNOI');
        $this->addSql('ALTER TABLE participation_request DROP FOREIGN KEY FK_PART_USER');
        $this->addSql('DROP TABLE participation_request');
    }
}
