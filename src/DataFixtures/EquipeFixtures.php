<?php

namespace App\DataFixtures;

use App\Entity\Equipe;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class EquipeFixtures extends Fixture implements DependentFixtureInterface
{
    public const EQUIPE_REFERENCE = 'equipe-';

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        $sports = ['Football', 'Basketball', 'Tennis', 'Volleyball', 'Rugby', 'Handball'];

        for ($i = 1; $i <= 20; $i++) {
            $equipe = new Equipe();
            
            $sport = $sports[array_rand($sports)];
            $equipe->setNom($faker->city() . ' ' . $faker->randomElement(['FC', 'AS', 'United', 'Stars', 'Team', 'Club']));
            $equipe->setSport($sport);
            $equipe->setCoach($faker->name());
            $equipe->setContact($faker->phoneNumber());

            // Add 3-8 users per team
            $nbPlayers = rand(3, 8);
            for ($j = 0; $j < $nbPlayers; $j++) {
                $userRef = UserFixtures::USER_REFERENCE . rand(1, 30);
                try {
                    /** @var User $user */
                    $user = $this->getReference($userRef, User::class);
                    $equipe->addUser($user);
                } catch (\Exception $e) {
                    // Reference not found, skip
                }
            }

            // Add 1-3 manual participants (non-registered players)
            $nbManual = rand(1, 3);
            $manualParticipants = [];
            for ($k = 0; $k < $nbManual; $k++) {
                $manualParticipants[] = $faker->firstName() . ' ' . $faker->lastName();
            }
            $equipe->setManualParticipants($manualParticipants);

            $manager->persist($equipe);
            $this->addReference(self::EQUIPE_REFERENCE . $i, $equipe);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
