<?php

namespace App\DataFixtures;

use App\Entity\Tournoi;
use App\Entity\Equipe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class TournoiFixtures extends Fixture implements DependentFixtureInterface
{
    public const TOURNOI_REFERENCE = 'tournoi-';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $formats = ['Élimination directe', 'Poules', 'Double élimination', 'Libre'];
        $sports = ['Football', 'Basketball', 'Tennis', 'Volleyball', 'Rugby', 'Handball'];

        for ($i = 1; $i <= 15; $i++) {
            $tournoi = new Tournoi();
            $tournoi->setNom('Tournoi ' . $faker->city() . ' ' . $faker->year());
            
            $sport = $sports[array_rand($sports)];
            $tournoi->setSport($sport);
            $tournoi->setFormat($formats[array_rand($formats)]);
            
            $dateDebut = $faker->dateTimeBetween('-2 months', '+3 months');
            $dateFin = (clone $dateDebut)->modify('+' . rand(1, 14) . ' days');
            
            $tournoi->setDateDebut(\DateTime::createFromInterface($dateDebut));
            $tournoi->setDateFin(\DateTime::createFromInterface($dateFin));
            
            // Assign creator (admin or random user)
            $creatorRef = $faker->boolean(30) ? UserFixtures::USER_ADMIN : UserFixtures::USER_REFERENCE . rand(1, 30);
            try {
                $tournoi->setCreator($this->getReference($creatorRef, \App\Entity\User::class));
            } catch (\Exception $e) {
                // Reference not found, skip
            }

            // Add 4-12 teams to tournament (only teams of same sport)
            $nbTeams = rand(4, 12);
            $addedTeams = 0;
            for ($attempt = 0; $attempt < 40 && $addedTeams < $nbTeams; $attempt++) {
                $equipeRef = EquipeFixtures::EQUIPE_REFERENCE . rand(1, 20);
                try {
                    /** @var Equipe $equipe */
                    $equipe = $this->getReference($equipeRef, Equipe::class);
                    // Only add if sport matches or equipe has no sport set
                    if ($equipe->getSport() === $sport || !$equipe->getSport()) {
                        if (!$tournoi->getEquipes()->contains($equipe)) {
                            $tournoi->addEquipe($equipe);
                            $addedTeams++;
                        }
                    }
                } catch (\Exception $e) {
                    // Reference not found, skip
                }
            }

            // 20% chance tournament has ended with a winner
            if ($faker->boolean(20) && $tournoi->getEquipes()->count() > 0) {
                $winner = $tournoi->getEquipes()->toArray()[array_rand($tournoi->getEquipes()->toArray())];
                $tournoi->setWinner($winner);
            }

            $manager->persist($tournoi);
            $this->addReference(self::TOURNOI_REFERENCE . $i, $tournoi);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            EquipeFixtures::class,
        ];
    }
}