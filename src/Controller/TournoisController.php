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
    public function index(ManagerRegistry $doctrine): Response
    {
    /** @var \App\Repository\TournoiRepository $repo */
    $repo = $doctrine->getRepository(Tournoi::class);

        // read search query from GET param 'q'
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : null;

        // use repository search helper
        if ($q !== null && $q !== '') {
            $tournois = $repo->findBySearch($q);
        } else {
            $tournois = $repo->findBy([], ['dateDebut' => 'DESC']);
        }

        return $this->render('tournois/index.html.twig', [
            'controller_name' => 'TournoisController',
            'tournois' => $tournois,
            'q' => $q,
        ]);
    }

    #[Route('/tournoi/{id}', name: 'app_tournoi_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(int $id, ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(Tournoi::class);
        $tournoi = $repo->find($id);

        if (!$tournoi) {
            throw $this->createNotFoundException('Tournoi non trouvé.');
        }

        return $this->render('tournois/show.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }

    #[Route('/tournoi/{id}/delete', name: 'app_tournoi_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(Tournoi::class);
        $tournoi = $repo->find($id);

        if (!$tournoi) {
            throw $this->createNotFoundException('Tournoi non trouvé.');
        }

        $submittedToken = $request->request->get('_token');

        if ($this->isCsrfTokenValid('delete'.$tournoi->getId(), $submittedToken)) {
            $em = $doctrine->getManager();
            $em->remove($tournoi);
            $em->flush();
            $this->addFlash('success', 'Tournoi supprimé.');
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        return $this->redirectToRoute('app_tournois');
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

    #[Route('/tournoi/{id}/edit', name: 'app_tournoi_edit', methods: ['GET','POST'], requirements: ['id' => '\\d+'])]
    public function edit(int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        $repo = $doctrine->getRepository(Tournoi::class);
        $tournoi = $repo->find($id);

        if (!$tournoi) {
            throw $this->createNotFoundException('Tournoi non trouv\u00e9.');
        }

        // Handle POST - simple form processing without a FormType for speed
        if ($request->isMethod('POST')) {
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('edit'.$tournoi->getId(), $submittedToken)) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
            }

            $nom = trim((string) $request->request->get('nom', ''));
            $sport = trim((string) $request->request->get('sport', ''));
            $format = trim((string) $request->request->get('format', ''));
            $dateDebutStr = $request->request->get('date_debut');
            $dateFinStr = $request->request->get('date_fin');

            if ($nom === '' || $sport === '') {
                $this->addFlash('error', 'Le nom et le sport sont requis.');
                return $this->redirectToRoute('app_tournoi_edit', ['id' => $tournoi->getId()]);
            }

            try {
                if (!empty($dateDebutStr)) {
                    $tournoi->setDateDebut(new DateTime($dateDebutStr));
                }
                if (!empty($dateFinStr)) {
                    $tournoi->setDateFin(new DateTime($dateFinStr));
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date invalide.');
                return $this->redirectToRoute('app_tournoi_edit', ['id' => $tournoi->getId()]);
            }

            $tournoi->setNom($nom);
            $tournoi->setSport($sport);
            $tournoi->setFormat($format !== '' ? $format : null);

            $em = $doctrine->getManager();
            $em->persist($tournoi);
            $em->flush();

            $this->addFlash('success', 'Tournoi mis \u00e0 jour.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        // GET - render edit form
        return $this->render('tournois/edit.html.twig', [
            'tournoi' => $tournoi,
        ]);
    }
}
