<?php

namespace App\Service;

use App\Entity\Tournoi;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Rencontre;

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

    /**
     * Create Rencontre entries in DB for supported formats. Idempotent: will not duplicate if rencontres exist.
     */
    public function persistMatches(EntityManagerInterface $em, Tournoi $tournoi): array
    {
        // do nothing if already has matches
        if ($tournoi->getRencontres() && count($tournoi->getRencontres()) > 0) {
            // return a structure describing the persisted matches grouped by round
            $repo = $em->getRepository(Rencontre::class);
            try {
                $matches = $repo->findBy(['tournoi' => $tournoi], ['round' => 'ASC', 'position' => 'ASC']);
            } catch (\Doctrine\DBAL\Exception $e) {
                // position column not available yet — fall back to ordering by round only
                $matches = $repo->findBy(['tournoi' => $tournoi], ['round' => 'ASC']);
            }
            $out = [];
            foreach ($matches as $m) {
                $r = $m->getRound() ?: 0;
                if (!isset($out[$r])) $out[$r] = ['round' => $r, 'matches' => []];
                $out[$r]['matches'][] = [
                    'id' => $m->getId(),
                    'a' => $m->getEquipes()?->getNom() ?? null,
                    'b' => $m->getEquipeVisiteur()?->getNom() ?? null,
                    'score_a' => $m->getScoreHome(),
                    'score_b' => $m->getScoreAway(),
                    'status' => $m->getStatus(),
                    'position' => $m->getPosition(),
                ];
            }
            // determine type from tournoi format
            $map = [
                'elimination_simple' => 'single_elimination',
                'double_elimination' => 'double_elimination',
                'round_robin' => 'round_robin',
                'libre' => 'libre',
            ];
            $type = $map[$tournoi->getFormat()] ?? null;

            return ['type' => $type, 'rounds' => array_values($out)];
        }

        // fallback: generate from current tournament teams
        $format = $tournoi->getFormat();
        $equipes = $tournoi->getEquipes()->toArray();

        $em->getConnection()->beginTransaction();
        try {
            if ($format === 'elimination_simple') {
                $structure = $this->generateSingleElimination($equipes);
                // persist matches per round
                $roundIndex = 1;
                foreach ($structure['rounds'] as $round) {
                    $pos = 0;
                    foreach ($round['matches'] as $m) {
                        $rencontre = new Rencontre();
                        $rencontre->setRound($roundIndex);
                        $rencontre->setBracket('winners');
                        $rencontre->setPosition($pos);
                        if (!empty($m['a'])) $rencontre->setEquipes($m['a'] instanceof \App\Entity\Equipe ? $m['a'] : null);
                        if (!empty($m['b'])) $rencontre->setEquipeVisiteur($m['b'] instanceof \App\Entity\Equipe ? $m['b'] : null);
                        $rencontre->setTournoi($tournoi);
                        $em->persist($rencontre);
                        $pos++;
                    }
                    $roundIndex++;
                }
                // For double elimination we still want to create initial placeholders for losers bracket
                if ($tournoi->getFormat() === 'double_elimination') {
                    // naive losers rounds count = number of winner rounds
                    $loserRounds = max(1, count($structure['rounds']));
                    for ($lr = 1; $lr <= $loserRounds; $lr++) {
                        // number of matches roughly half of previous
                        $matchesCount = max(1, intdiv(count($structure['rounds'][0]['matches']), (1 << ($lr - 1))));
                        for ($p = 0; $p < $matchesCount; $p++) {
                            $renc = new Rencontre();
                            $renc->setRound($lr);
                            $renc->setPosition($p);
                            $renc->setBracket('losers');
                            $renc->setTournoi($tournoi);
                            $em->persist($renc);
                        }
                    }
                }
            } elseif ($format === 'round_robin') {
                // structure from generateRoundRobin outputs arrays using team objects; create rencontres
                $structure = $this->generateRoundRobin($equipes);
                $roundIndex = 1;
                foreach ($structure['rounds'] as $round) {
                    $pos = 0;
                    foreach ($round['matches'] as $m) {
                        $rencontre = new Rencontre();
                        $rencontre->setRound($roundIndex);
                        $rencontre->setPosition($pos);
                        if (!empty($m['a']) && $m['a']['id']) {
                            $a = $em->getRepository(\App\Entity\Equipe::class)->find($m['a']['id']);
                            $rencontre->setEquipes($a);
                        }
                        if (!empty($m['b']) && $m['b']['id']) {
                            $b = $em->getRepository(\App\Entity\Equipe::class)->find($m['b']['id']);
                            $rencontre->setEquipeVisiteur($b);
                        }
                        $rencontre->setTournoi($tournoi);
                        $em->persist($rencontre);
                        $pos++;
                    }
                    $roundIndex++;
                }
            }

            $em->flush();
            $em->getConnection()->commit();
        } catch (\Throwable $e) {
            $em->getConnection()->rollBack();
            throw $e;
        }

        // return generated structure for immediate frontend rendering
        $result = $this->persistMatches($em, $tournoi);
        // persistMatches now returns ['type'=>..., 'rounds'=>...]
        return $result;
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
                
                // Convert teams to display strings
                $aDisplay = $a ? (is_array($a) ? $a['name'] : $a->getNom()) : null;
                $bDisplay = $b ? (is_array($b) ? $b['name'] : $b->getNom()) : null;
                
                if ($b === null) {
                    // bye
                    $matches[] = ['a' => $aDisplay, 'b' => null];
                } else {
                    $matches[] = ['a' => $aDisplay, 'b' => $bDisplay];
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
                
                // Convert teams to display strings
                $aDisplay = is_array($a) ? $a['name'] : $a->getNom();
                $bDisplay = is_array($b) ? $b['name'] : $b->getNom();
                
                $matches[] = ['a' => $aDisplay, 'b' => $bDisplay];
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
