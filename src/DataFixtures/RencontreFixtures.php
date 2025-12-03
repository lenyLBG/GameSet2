<?php

namespace App\DataFixtures;

use App\Entity\Rencontre;
use App\Entity\Tournoi;
use App\Entity\Equipe;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class RencontreFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Create matches for each tournament
        for ($i = 1; $i <= 15; $i++) {
            $tournoiRef = TournoiFixtures::TOURNOI_REFERENCE . $i;
            try {
                /** @var Tournoi $tournoi */
                $tournoi = $this->getReference($tournoiRef, Tournoi::class);
            } catch (\Exception $e) {
                continue;
            }

            $equipes = $tournoi->getEquipes()->toArray();

            if (count($equipes) < 2) {
                continue; // Need at least 2 teams for matches
            }

            // Create 3-8 matches per tournament
            $nbMatches = rand(3, min(8, count($equipes) * 2));
            
            for ($j = 0; $j < $nbMatches; $j++) {
                $rencontre = new Rencontre();
                
                // Pick two different teams
                shuffle($equipes);
                /** @var Equipe $teamHome */
                $teamHome = $equipes[0];
                /** @var Equipe $teamAway */
                $teamAway = $equipes[1];

                $rencontre->setEquipes($teamHome);
                $rencontre->setEquipeVisiteur($teamAway);
                $rencontre->setTournoi($tournoi);
                
                $rencontre->setRound(rand(1, 4));
                $rencontre->setPosition($j);
                $rencontre->setBracket($faker->randomElement(['winners', 'losers', 'final']));

                // 60% of matches are completed with scores
                if ($faker->boolean(60)) {
                    $rencontre->setStatus('completed');
                    $scoreHome = rand(0, 5);
                    $scoreAway = rand(0, 5);
                    $rencontre->setScoreHome($scoreHome);
                    $rencontre->setScoreAway($scoreAway);
                    
                    // Determine winner
                    if ($scoreHome > $scoreAway) {
                        $rencontre->setWinner($teamHome);
                        $rencontre->setPoints(3);
                    } elseif ($scoreAway > $scoreHome) {
                        $rencontre->setWinner($teamAway);
                        $rencontre->setPoints(3);
                    } else {
                        // Draw - no winner, 1 point each
                        $rencontre->setPoints(1);
                    }
                } else {
                    $rencontre->setStatus('pending');
                }

                $manager->persist($rencontre);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TournoiFixtures::class,
            EquipeFixtures::class,
        ];
    }
}
