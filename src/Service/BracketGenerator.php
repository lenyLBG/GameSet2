<?php

namespace App\Service;

use App\Entity\Tournoi;

class BracketGenerator
{
    /**
     * Generate a simple structure representing the bracket or schedule.
     * Returns an array with different shapes depending on format.
     *
     * @return array|null
     */
    public function generate(Tournoi $tournoi): ?array
    {
        $format = $tournoi->getFormat();
        $equipes = $tournoi->getEquipes()->toArray();

        // map equipes to simple items
        $teams = array_map(function($e){ return ['id' => $e->getId(), 'name' => $e->getNom()]; }, $equipes);

        switch ($format) {
            case 'elimination_simple':
                return $this->generateSingleElimination($teams);
            case 'double_elimination':
                return $this->generateDoubleElimination($teams);
            case 'round_robin':
                return $this->generateRoundRobin($teams);
            case 'libre':
            default:
                return null;
        }
    }

    private function generateSingleElimination(array $teams): array
    {
        // simple pairing by order; if odd, give bye to last
        $rounds = [];
        $current = $teams;
        $roundIndex = 1;

        while (count($current) > 1) {
            $matches = [];
            $i = 0;
            $len = count($current);
            while ($i < $len) {
                $a = $current[$i] ?? null;
                $b = $current[$i+1] ?? null;
                if ($b === null) {
                    // bye
                    $matches[] = ['a' => $a, 'b' => null];
                } else {
                    $matches[] = ['a' => $a, 'b' => $b];
                }
                $i += 2;
            }
            $rounds[] = ['round' => $roundIndex, 'matches' => $matches];

            // winners placeholder - number of matches
            $next = array_fill(0, count($matches), ['id' => null, 'name' => 'Winner']);
            $current = $next;
            $roundIndex++;
        }

        return ['type' => 'single_elimination', 'rounds' => $rounds];
    }

    private function generateDoubleElimination(array $teams): array
    {
        // Simplified: generate winners bracket same as single elimination
        $winners = $this->generateSingleElimination($teams);
        // losers bracket placeholder - empty structure for now
        $losers = ['type' => 'double_elimination_losers', 'rounds' => []];

        return ['type' => 'double_elimination', 'winners' => $winners, 'losers' => $losers];
    }

    private function generateRoundRobin(array $teams): array
    {
        $n = count($teams);
        if ($n <= 1) {
            return ['type' => 'round_robin', 'rounds' => []];
        }

        $rotating = $teams;
        // if odd, add bye
        $hasBye = false;
        if ($n % 2 === 1) {
            $rotating[] = ['id' => null, 'name' => 'BYE'];
            $hasBye = true;
            $n++;
        }

        $rounds = [];
        for ($r = 0; $r < $n - 1; $r++) {
            $matches = [];
            for ($i = 0; $i < $n / 2; $i++) {
                $a = $rotating[$i];
                $b = $rotating[$n - 1 - $i];
                $matches[] = ['a' => $a, 'b' => $b];
            }
            $rounds[] = ['round' => $r + 1, 'matches' => $matches];

            // rotate (except first fixed)
            $first = array_shift($rotating);
            $last = array_pop($rotating);
            array_unshift($rotating, $first);
            array_push($rotating, $last);
            // perform rotation step
            $second = array_shift($rotating);
            array_push($rotating, $second);
        }

        return ['type' => 'round_robin', 'rounds' => $rounds, 'has_bye' => $hasBye];
    }
}
