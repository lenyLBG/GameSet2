<?php

namespace App\DataFixtures;

use App\Entity\ParticipationRequest;
use App\Entity\Tournoi;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ParticipationRequestFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Create 10-20 participation requests
        for ($i = 0; $i < rand(10, 20); $i++) {
            $request = new ParticipationRequest();

            // Random user and tournament
            $userRef = UserFixtures::USER_REFERENCE . rand(1, 30);
            $tournoiRef = TournoiFixtures::TOURNOI_REFERENCE . rand(1, 15);

            try {
                /** @var User $user */
                $user = $this->getReference($userRef, User::class);
                /** @var Tournoi $tournoi */
                $tournoi = $this->getReference($tournoiRef, Tournoi::class);
            } catch (\Exception $e) {
                continue;
            }

            $request->setUser($user);
            $request->setTournoi($tournoi);
            $request->setStatus($faker->randomElement(['pending', 'accepted', 'rejected']));
            
            if ($faker->boolean(70)) {
                $request->setMessage($faker->sentence(10));
            }

            $createdAt = $faker->dateTimeBetween('-30 days', 'now');
            $request->setCreatedAt(\DateTimeImmutable::createFromMutable($createdAt));

            $manager->persist($request);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            TournoiFixtures::class,
        ];
    }
}
