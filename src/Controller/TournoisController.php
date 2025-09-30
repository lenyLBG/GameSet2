<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Tournoi;
use Doctrine\Persistence\ManagerRegistry;

final class TournoisController extends AbstractController
{
    #[Route('/tournoi', name: 'app_tournois')]
    public function index(): Response
    {
        return $this->render('tournois/index.html.twig', [
            'controller_name' => 'TournoisController',
        ]);
    }

    #[Route('/tournoi/create', name: 'app_tournoi_create', methods: ['POST'])]
    public function create(Request $request, ManagerRegistry $doctrine): Response
    {
        $nom = trim((string) $request->request->get('nom', ''));
        $sport = trim((string) $request->request->get('sport', ''));
        $format = trim((string) $request->request->get('format', ''));
        $dateDebutStr = $request->request->get('date_debut');
        $dateFinStr = $request->request->get('date_fin');

        // Basic validation
        if ($nom === '' || $sport === '') {
            $this->addFlash('error', 'Le nom et le sport sont requis.');
            return $this->redirectToRoute('app_tournois');
        }

        // Parse dates (expecting YYYY-MM-DD from input[type=date])
        $dateDebut = null;
        $dateFin = null;
        try {
            if (!empty($dateDebutStr)) {
                $dateDebut = new \DateTime($dateDebutStr);
            }
            if (!empty($dateFinStr)) {
                $dateFin = new \DateTime($dateFinStr);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Format de date invalide.');
            return $this->redirectToRoute('app_tournois');
        }

        $tournoi = new Tournoi();
        $tournoi->setNom($nom);
        $tournoi->setSport($sport);
        $tournoi->setFormat($format !== '' ? $format : null);
        if ($dateDebut) {
            $tournoi->setDateDebut($dateDebut);
        } else {
            // fallback to today if not provided
            $tournoi->setDateDebut(new \DateTime());
        }
        if ($dateFin) {
            $tournoi->setDateFin($dateFin);
        }

        $em = $doctrine->getManager();
        $em->persist($tournoi);
        $em->flush();

        $this->addFlash('success', 'Tournoi créé.');

        return $this->redirectToRoute('app_tournois');
    }
}
