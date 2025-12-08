<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930061116 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE classement (id INT AUTO_INCREMENT NOT NULL, diff_buts INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, coach VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, sport VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipe_tournoi (equipe_id INT NOT NULL, tournoi_id INT NOT NULL, INDEX IDX_C0AFC5636D861B89 (equipe_id), INDEX IDX_C0AFC563F607770A (tournoi_id), PRIMARY KEY(equipe_id, tournoi_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rencontre (id INT AUTO_INCREMENT NOT NULL, terrains_id INT DEFAULT NULL, equipes_id INT DEFAULT NULL, equipe_visiteur_id INT DEFAULT NULL, round INT DEFAULT NULL, points INT NOT NULL, INDEX IDX_460C35EDE620E1A8 (terrains_id), INDEX IDX_460C35ED737800BA (equipes_id), INDEX IDX_460C35EDB5E2C2ED (equipe_visiteur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE terrains (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_equipe (user_id INT NOT NULL, equipe_id INT NOT NULL, INDEX IDX_411BA128A76ED395 (user_id), INDEX IDX_411BA1286D861B89 (equipe_id), PRIMARY KEY(user_id, equipe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE equipe_tournoi ADD CONSTRAINT FK_C0AFC5636D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_tournoi ADD CONSTRAINT FK_C0AFC563F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_460C35EDE620E1A8 FOREIGN KEY (terrains_id) REFERENCES terrains (id)');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_460C35ED737800BA FOREIGN KEY (equipes_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE rencontre ADD CONSTRAINT FK_460C35EDB5E2C2ED FOREIGN KEY (equipe_visiteur_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE user_equipe ADD CONSTRAINT FK_411BA128A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_equipe ADD CONSTRAINT FK_411BA1286D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user ADD nom VARCHAR(255) NOT NULL, ADD prenom VARCHAR(255) NOT NULL, ADD licence VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipe_tournoi DROP FOREIGN KEY FK_C0AFC5636D861B89');
        $this->addSql('ALTER TABLE equipe_tournoi DROP FOREIGN KEY FK_C0AFC563F607770A');
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_460C35EDE620E1A8');
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_460C35ED737800BA');
        $this->addSql('ALTER TABLE rencontre DROP FOREIGN KEY FK_460C35EDB5E2C2ED');
        $this->addSql('ALTER TABLE user_equipe DROP FOREIGN KEY FK_411BA128A76ED395');
        $this->addSql('ALTER TABLE user_equipe DROP FOREIGN KEY FK_411BA1286D861B89');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE classement');
        $this->addSql('DROP TABLE equipe');
        $this->addSql('DROP TABLE equipe_tournoi');
        $this->addSql('DROP TABLE rencontre');
        $this->addSql('DROP TABLE terrains');
        $this->addSql('DROP TABLE user_equipe');
        $this->addSql('ALTER TABLE user DROP nom, DROP prenom, DROP licence');
    }
}
