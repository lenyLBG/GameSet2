<?php

namespace App\Controller;

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
        $nom = $request->request->get('nom');
        $description = $request->request->get('description');

        // Minimal validation
        if (!$nom) {
            $this->addFlash('error', 'Le nom est requis.');
            return $this->redirectToRoute('app_tournois');
        }

        $tournoi = new Tournoi();
        $tournoi->setNom($nom);
        // store description in "format" if you don't have a dedicated field
        $tournoi->setFormat($description ?: null);
        $tournoi->setSport('inconnu');
        $tournoi->setDateDebut(new \DateTime());

        $em = $doctrine->getManager();
        $em->persist($tournoi);
        $em->flush();

        $this->addFlash('success', 'Tournoi créé.');

        return $this->redirectToRoute('app_tournois');
    }
}
