<?php

namespace App\Controller;

use App\Repository\TournoiRepository;
use App\Repository\EquipeRepository;
use App\Repository\RencontreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatistiquesController extends AbstractController
{
    #[Route('/statistique', name: 'app_statistiques')]
    public function index(
        TournoiRepository $tournoiRepository,
        EquipeRepository $equipeRepository,
        RencontreRepository $rencontreRepository
    ): Response
    {
        // Get all tournaments
        $allTournois = $tournoiRepository->findAll();
        $totalTournois = count($allTournois);

        // Get completed tournaments
        $completedTournois = array_filter($allTournois, function($t) {
            return $t->getDateFin() !== null && $t->getDateFin() < new \DateTime();
        });
        $totalCompletedTournois = count($completedTournois);

        // Get ongoing tournaments
        $ongoingTournois = array_filter($allTournois, function($t) {
            return $t->getDateFin() === null || $t->getDateFin() >= new \DateTime();
        });
        $totalOngoingTournois = count($ongoingTournois);

        // Get all teams
        $totalEquipes = count($equipeRepository->findAll());

        // Get tournament data for last 6 months for chart
        $chartData = $this->getChartData($tournoiRepository);

        return $this->render('statistiques/index.html.twig', [
            'total_tournois' => $totalTournois,
            'total_equipes' => $totalEquipes,
            'total_completed' => $totalCompletedTournois,
            'total_ongoing' => $totalOngoingTournois,
            'chart_data' => $chartData,
        ]);
    }

    private function getChartData(TournoiRepository $tournoiRepository): array
    {
        $monthsData = [];
        $now = new \DateTime();

        for ($i = 5; $i >= 0; $i--) {
            $date = (clone $now)->modify("-$i months");
            $month = $date->format('Y-m');
            $monthLabel = $date->format('M');

            $tournois = $tournoiRepository->findAll();
            $count = 0;
            foreach ($tournois as $t) {
                if ($t->getDateDebut()) {
                    if ($t->getDateDebut()->format('Y-m') === $month) {
                        $count++;
                    }
                }
            }

            $monthsData[$monthLabel] = $count;
        }

        return $monthsData;
    }
}
