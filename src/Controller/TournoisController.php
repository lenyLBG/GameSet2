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

        // Only creator or admin can delete
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut le supprimer.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
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
        // Require authentication
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour créer un tournoi.');
            return $this->redirectToRoute('app_tournois');
        }

        // Validate CSRF token
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('create_tournoi', $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournois');
        }
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

        // set creator to current user
        $user = $this->getUser();
        if ($user) {
            $tournoi->setCreator($user);
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

        // Only creator or admin can edit
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut le modifier.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
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

    #[Route('/tournoi/{id}/add-participant', name: 'app_tournoi_add_participant', methods: ['POST'])]
    public function addParticipant(int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        // Require authentication
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter des participants.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        // CSRF check
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_participant_'.$id, $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $nom = trim((string) $request->request->get('nom', ''));
        $coach = trim((string) $request->request->get('coach', ''));
        $contact = trim((string) $request->request->get('contact', ''));

        if ($nom === '') {
            $this->addFlash('error', 'Le nom de l\'équipe est requis.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $em = $doctrine->getManager();
        $tournoiRepo = $doctrine->getRepository(\App\Entity\Tournoi::class);
        $equipeRepo = $doctrine->getRepository(\App\Entity\Equipe::class);

        $tournoi = $tournoiRepo->find($id);
        if (!$tournoi) {
            $this->addFlash('error', 'Tournoi introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        // Only creator or admin can add participants
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut ajouter des participants.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        // Try to find existing equipe by name
        $existing = $equipeRepo->findOneBy(['nom' => $nom]);
        if ($existing) {
            $equipe = $existing;
        } else {
            $equipe = new \App\Entity\Equipe();
            $equipe->setNom($nom);
            $equipe->setCoach($coach !== '' ? $coach : null);
            $equipe->setContact($contact !== '' ? $contact : null);
            $em->persist($equipe);
        }

        // Associate equipe with tournoi if not already
        if (!$equipe->getTournois()->contains($tournoi)) {
            $equipe->addTournoi($tournoi);
            $tournoi->addEquipe($equipe);
            $em->persist($tournoi);
            $em->flush();
            $this->addFlash('success', 'Participant ajouté au tournoi.');
        } else {
            $this->addFlash('info', 'L\'équipe est déjà inscrite à ce tournoi.');
        }

        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/tournoi/{id}/remove-participant/{equipeId}', name: 'app_tournoi_remove_participant', methods: ['POST'])]
    public function removeParticipant(int $id, int $equipeId, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier les participants.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('remove_participant_'.$id.'_'.$equipeId, $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $em = $doctrine->getManager();
        $tournoi = $doctrine->getRepository(\App\Entity\Tournoi::class)->find($id);
        $equipe = $doctrine->getRepository(\App\Entity\Equipe::class)->find($equipeId);

        if (!$tournoi || !$equipe) {
            $this->addFlash('error', 'Ressource introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        // Only creator or admin can remove participants
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut modifier les participants.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        if ($equipe->getTournois()->contains($tournoi)) {
            $equipe->removeTournoi($tournoi);
            $tournoi->removeEquipe($equipe);
            $em->persist($equipe);
            $em->persist($tournoi);
            $em->flush();
            $this->addFlash('success', 'Participant retiré.');
        } else {
            $this->addFlash('info', 'L\'équipe n\'est pas inscrite à ce tournoi.');
        }

        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }
}
