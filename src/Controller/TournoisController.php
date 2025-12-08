<?php

namespace App\Controller;

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Tournoi;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\ParticipationRequest;
use App\Entity\Equipe;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
    public function show(int $id, ManagerRegistry $doctrine, \App\Service\BracketGenerator $bracketGenerator): Response
    {
        $repo = $doctrine->getRepository(Tournoi::class);
        $tournoi = $repo->find($id);

        if (!$tournoi) {
            throw $this->createNotFoundException('Tournoi non trouvé.');
        }

        // only load pending requests
        $requests = $doctrine->getRepository(ParticipationRequest::class)->findBy(['tournoi' => $tournoi, 'status' => 'pending'], ['createdAt' => 'DESC']);

        // generate bracket/schedule for the selected format
        // Prefer persisted rencontres if present; else generate transient bracket
        $em = $doctrine->getManager();
        try {
            $rencontres = $doctrine->getRepository(\App\Entity\Rencontre::class)->findBy(['tournoi' => $tournoi], ['round' => 'ASC', 'position' => 'ASC']);
        } catch (\Doctrine\DBAL\Exception $e) {
            // If the 'position' column doesn't exist yet (migration not applied), fall back to ordering by round only
            $rencontres = $doctrine->getRepository(\App\Entity\Rencontre::class)->findBy(['tournoi' => $tournoi], ['round' => 'ASC']);
        }

        if ($rencontres && count($rencontres) > 0) {
            // build bracket structure from rencontres
            // Support double-elimination separation of winners and losers
            $winners = [];
            $losers = [];

            foreach ($rencontres as $m) {
                $r = $m->getRound() ?: 0;
                $bucket = ($m->getBracket() === 'losers') ? 'losers' : 'winners';
                ${$bucket}[$r] = ${$bucket}[$r] ?? ['round' => $r, 'matches' => []];
                
                // Get team A players
                $teamAPlayers = [];
                if ($m->getEquipes()) {
                    $teamAUsers = array_map(fn($u) => $u->getPrenom() . ' ' . $u->getNom(), $m->getEquipes()->getUsers()->toArray());
                    $teamAManual = $m->getEquipes()->getManualParticipants();
                    $teamAPlayers = array_merge($teamAUsers, $teamAManual);
                }
                $teamADisplay = !empty($teamAPlayers) ? implode(', ', $teamAPlayers) : ($m->getEquipes()?->getNom() ?? null);
                
                // Get team B players
                $teamBPlayers = [];
                if ($m->getEquipeVisiteur()) {
                    $teamBUsers = array_map(fn($u) => $u->getPrenom() . ' ' . $u->getNom(), $m->getEquipeVisiteur()->getUsers()->toArray());
                    $teamBManual = $m->getEquipeVisiteur()->getManualParticipants();
                    $teamBPlayers = array_merge($teamBUsers, $teamBManual);
                }
                $teamBDisplay = !empty($teamBPlayers) ? implode(', ', $teamBPlayers) : ($m->getEquipeVisiteur()?->getNom() ?? null);
                
                ${$bucket}[$r]['matches'][] = [
                    'id' => $m->getId(),
                    'a' => $teamADisplay,
                    'a_team' => $m->getEquipes()?->getNom() ?? null,
                    'a_id' => $m->getEquipes()?->getId() ?? null,
                    'b' => $teamBDisplay,
                    'b_team' => $m->getEquipeVisiteur()?->getNom() ?? null,
                    'b_id' => $m->getEquipeVisiteur()?->getId() ?? null,
                    'score_a' => $m->getScoreHome(),
                    'score_b' => $m->getScoreAway(),
                    'status' => $m->getStatus(),
                    'position' => $m->getPosition(),
                    'winner_id' => $m->getWinner()?->getId() ?? null,
                ];
            }

            $winners = array_values($winners);
            $losers = array_values($losers);

            $type = $tournoi->getFormat() === 'double_elimination' ? 'double_elimination' : ($tournoi->getFormat() === 'elimination_simple' ? 'single_elimination' : ($tournoi->getFormat() === 'round_robin' ? 'round_robin' : 'libre'));

            if ($type === 'double_elimination') {
                $bracket = ['type' => $type, 'winners' => $winners, 'losers' => $losers];
            } else {
                $bracket = ['type' => $type, 'rounds' => $winners];
            }
        } else {
            $bracket = $bracketGenerator->generate($tournoi);
        }

        $userRepo = $doctrine->getRepository(\App\Entity\User::class);
        $allUsers = $userRepo->findAll();

        return $this->render('tournois/show.html.twig', [
            'tournoi' => $tournoi,
            'participation_requests' => $requests,
            'bracket' => $bracket,
            'all_users' => $allUsers,
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

    #[Route('/tournoi/{id}/edit-participant/{equipeId}', name: 'app_tournoi_edit_participant', methods: ['POST'])]
    public function editParticipant(int $id, int $equipeId, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour modifier les participants.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('edit_participant_'.$id.'_'.$equipeId, $submittedToken)) {
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

        // Only creator or admin can edit participants
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut modifier les participants.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $nom = trim((string) $request->request->get('nom', ''));
        $coach = trim((string) $request->request->get('coach', ''));
        $contact = trim((string) $request->request->get('contact', ''));

        if ($nom === '') {
            $this->addFlash('error', 'Le nom de l\'équipe est requis.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $equipe->setNom($nom);
        $equipe->setCoach($coach !== '' ? $coach : null);
        $equipe->setContact($contact !== '' ? $contact : null);
        $em->persist($equipe);
        $em->flush();
        $this->addFlash('success', 'Participant modifié avec succès.');

        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/tournoi/{id}/add-player/{equipeId}', name: 'app_tournoi_add_player', methods: ['POST'])]
    public function addPlayerToTeam(int $id, int $equipeId, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter des joueurs.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('edit_participant_'.$id.'_'.$equipeId, $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $em = $doctrine->getManager();
        $tournoi = $doctrine->getRepository(\App\Entity\Tournoi::class)->find($id);
        $equipe = $doctrine->getRepository(\App\Entity\Equipe::class)->find($equipeId);
        $userId = $request->request->get('user_id');
        $user = $userId ? $doctrine->getRepository(\App\Entity\User::class)->find($userId) : null;

        if (!$tournoi || !$equipe || !$user) {
            $this->addFlash('error', 'Ressource introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        // Only creator or admin can add players
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut ajouter des joueurs.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        if (!$equipe->getUsers()->contains($user)) {
            $equipe->addUser($user);
            $user->addEquipe($equipe);
            $em->persist($equipe);
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Joueur ajouté à l\'équipe.');
        } else {
            $this->addFlash('info', 'Ce joueur est déjà dans l\'équipe.');
        }

        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/tournoi/{id}/add-manual-player/{equipeId}', name: 'app_tournoi_add_manual_player', methods: ['POST'])]
    public function addManualPlayerToTeam(int $id, int $equipeId, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour ajouter des participants.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('edit_participant_'.$id.'_'.$equipeId, $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $em = $doctrine->getManager();
        $tournoi = $doctrine->getRepository(\App\Entity\Tournoi::class)->find($id);
        $equipe = $doctrine->getRepository(\App\Entity\Equipe::class)->find($equipeId);
        $participantName = trim((string) $request->request->get('participant_name', ''));

        if (!$tournoi || !$equipe) {
            $this->addFlash('error', 'Ressource introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        if ($participantName === '') {
            $this->addFlash('error', 'Le nom du participant est requis.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        // Only creator or admin can add players
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut ajouter des participants.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $equipe->addManualParticipant($participantName);
        $em->persist($equipe);
        $em->flush();
        $this->addFlash('success', 'Participant ajouté à l\'équipe.');

        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/participation-request/{id}/decide', name: 'app_participation_request_decide', methods: ['POST'])]
    public function decideParticipation(int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        $pr = $doctrine->getRepository(ParticipationRequest::class)->find($id);
        if (!$pr) {
            $this->addFlash('error', 'Demande introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        $tournoi = $pr->getTournoi();
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('decide_part_'.$pr->getId(), $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        $action = $request->request->get('action');
        $em = $doctrine->getManager();
        if ($action === 'accept') {
            $pr->setStatus('accepted');

            // Add requester as participant: try to use an existing Equipe linked to the user,
            // otherwise create a new Equipe for the user and associate it.
            $requester = $pr->getUser();
            $equipe = null;
            if ($requester) {
                $userEquipes = $requester->getEquipe();
                if ($userEquipes && count($userEquipes) > 0) {
                    // pick first equipe
                    $equipe = $userEquipes->first();
                }
            }

            if (!$equipe) {
                $equipe = new Equipe();
                $name = '';
                if ($requester) {
                    $name = trim(($requester->getNom() ?? '') . ' ' . ($requester->getPrenom() ?? '')) ?: 'Equipe ' . $requester->getId();
                } else {
                    $name = 'Equipe ' . uniqid();
                }
                $equipe->setNom($name);
                if ($requester && method_exists($equipe, 'setCoach')) {
                    // leave coach null; set contact if desired
                }
                // link user to equipe if mapping exists
                if ($requester && method_exists($requester, 'addEquipe')) {
                    $requester->addEquipe($equipe);
                }
                $em->persist($equipe);
            }

            // associate equipe with tournoi if not already
            if ($equipe && !$equipe->getTournois()->contains($tournoi)) {
                $equipe->addTournoi($tournoi);
                $tournoi->addEquipe($equipe);
                $em->persist($tournoi);
                $em->persist($equipe);
            }

            $this->addFlash('success', 'Demande acceptée. Le participant a été ajouté au tournoi.');
        } else {
            $pr->setStatus('rejected');
            $this->addFlash('success', 'Demande refusée.');
        }

        // Remove the request after decision so it no longer appears in pending list
        $em->remove($pr);
        $em->flush();

        return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
    }

    #[Route('/tournoi/{id}/request-participation', name: 'app_tournoi_request_participation', methods: ['POST'])]
    public function requestParticipation(int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour faire une demande de participation.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('request_participation_'.$id, $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $tournoi = $doctrine->getRepository(\App\Entity\Tournoi::class)->find($id);
        if (!$tournoi) {
            $this->addFlash('error', 'Tournoi introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        // Creators and admins do not need to request
        if ($this->getUser() === $tournoi->getCreator() || $this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('info', 'Vous êtes déjà autorisé pour ce tournoi.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }


        // Persist participation request in DB
        $em = $doctrine->getManager();
        $existing = $doctrine->getRepository(ParticipationRequest::class)->findOneBy(['tournoi' => $tournoi, 'user' => $this->getUser()]);
        if ($existing) {
            $this->addFlash('info', 'Vous avez déjà fait une demande pour ce tournoi.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $pr = new ParticipationRequest();
        $pr->setTournoi($tournoi);
        $pr->setUser($this->getUser());
        $message = trim((string) $request->request->get('message', ''));
        if ($message !== '') {
            $pr->setMessage($message);
        }
        $em->persist($pr);
        $em->flush();

        $this->addFlash('success', 'Votre demande de participation a été envoyée au créateur.');

        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/tournoi/{id}/generate-bracket', name: 'app_tournoi_generate_bracket', methods: ['POST'])]
    public function generateBracket(int $id, Request $request, ManagerRegistry $doctrine, \App\Service\BracketGenerator $bracketGenerator): Response
    {
        $repo = $doctrine->getRepository(Tournoi::class);
        $tournoi = $repo->find($id);
        if (!$tournoi) {
            $this->addFlash('error', 'Tournoi introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        // Only creator or admin can generate bracket
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('generate_bracket_'.$tournoi->getId(), $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        $em = $doctrine->getManager();
        try {
            $structure = $bracketGenerator->persistMatches($em, $tournoi);
            $this->addFlash('success', 'Tableau généré.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Erreur lors de la génération du tableau: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
    }

    #[Route('/rencontre/{id}/score', name: 'app_rencontre_score', methods: ['POST'])]
    public function scoreRencontre(int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        $em = $doctrine->getManager();
        $repo = $em->getRepository(\App\Entity\Rencontre::class);
        $rencontre = $repo->find($id);
        if (!$rencontre) {
            $this->addFlash('error', 'Rencontre introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        $tournoi = $rencontre->getTournoi();
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Action non autorisée.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('score_rencontre_'.$rencontre->getId(), $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        $scoreA = $request->request->getInt('score_a');
        $scoreB = $request->request->getInt('score_b');

        $rencontre->setScoreHome($scoreA);
        $rencontre->setScoreAway($scoreB);
        $rencontre->setStatus('completed');
        $em->persist($rencontre);

        // Determine winner and advance for elimination formats
        if (in_array($tournoi->getFormat(), ['elimination_simple', 'double_elimination'], true)) {
            if ($scoreA !== null || $scoreB !== null) {
                // decide winner/loser (draw => do nothing)
                if ($scoreA > $scoreB) {
                    $winner = $rencontre->getEquipes();
                    $loser = $rencontre->getEquipeVisiteur();
                } elseif ($scoreB > $scoreA) {
                    $winner = $rencontre->getEquipeVisiteur();
                    $loser = $rencontre->getEquipes();
                } else {
                    $winner = null; // draw -> do not advance automatically
                    $loser = null;
                }

                if ($winner) {
                    $currentRound = $rencontre->getRound() ?: 0;
                    $position = $rencontre->getPosition() ?: 0;

                    // Single-elimination: advance winner in the same "winners" flow
                    if ($tournoi->getFormat() === 'elimination_simple') {
                        $nextRound = $currentRound + 1;
                        $targetPos = (int) floor($position / 2);
                        try {
                            $nextMatch = $repo->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos]);
                        } catch (\Doctrine\DBAL\Exception $e) {
                            // if position column missing, try to find first match in next round
                            $nextMatch = $repo->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound]);
                        }
                        if ($nextMatch) {
                            if (($position % 2) === 0) {
                                if (!$nextMatch->getEquipes()) {
                                    $nextMatch->setEquipes($winner);
                                }
                            } else {
                                if (!$nextMatch->getEquipeVisiteur()) {
                                    $nextMatch->setEquipeVisiteur($winner);
                                }
                            }
                            $em->persist($nextMatch);
                        }
                    }

                    // Double-elimination: handle both winners and losers brackets
                    if ($tournoi->getFormat() === 'double_elimination') {
                        // If this match belongs to the winners bracket, advance winner in winners bracket and send loser to losers round 1
                        if ($rencontre->getBracket() === 'winners') {
                            // advance winner inside winners bracket
                            $nextRound = $currentRound + 1;
                            $targetPos = (int) floor($position / 2);
                            try {
                                $nextMatch = $repo->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos, 'bracket' => 'winners']);
                            } catch (\Doctrine\DBAL\Exception $e) {
                                // If the 'bracket' column doesn't exist yet, fall back to searching without it.
                                $nextMatch = $repo->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos]);
                            }
                            if ($nextMatch) {
                                if (($position % 2) === 0) {
                                    if (!$nextMatch->getEquipes()) $nextMatch->setEquipes($winner);
                                } else {
                                    if (!$nextMatch->getEquipeVisiteur()) $nextMatch->setEquipeVisiteur($winner);
                                }
                                $em->persist($nextMatch);
                            }

                            // place loser into first available slot in losers round 1
                            if ($loser) {
                                try {
                                    $loserTarget = $repo->findOneBy(['tournoi' => $tournoi, 'round' => 1, 'position' => $position, 'bracket' => 'losers']);
                                } catch (\Doctrine\DBAL\Exception $e) {
                                    // If 'bracket' doesn't exist yet in DB, search by round/position only
                                    $loserTarget = $repo->findOneBy(['tournoi' => $tournoi, 'round' => 1, 'position' => $position]);
                                    if (!$loserTarget) {
                                        // fallback further to any match from round 1
                                        $loserTarget = $repo->findOneBy(['tournoi' => $tournoi, 'round' => 1]);
                                    }
                                }
                                if ($loserTarget) {
                                    if (($position % 2) === 0) {
                                        if (!$loserTarget->getEquipes()) $loserTarget->setEquipes($loser);
                                    } else {
                                        if (!$loserTarget->getEquipeVisiteur()) $loserTarget->setEquipeVisiteur($loser);
                                    }
                                    $em->persist($loserTarget);
                                }
                            }

                        } elseif ($rencontre->getBracket() === 'losers') {
                            // winners in losers bracket advance within losers rounds
                            $nextRound = $currentRound + 1;
                            $targetPos = (int) floor($position / 2);
                            try {
                                $nextMatch = $repo->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos, 'bracket' => 'losers']);
                            } catch (\Doctrine\DBAL\Exception $e) {
                                // If 'bracket' column isn't present yet, fall back to matching just by round/position
                                $nextMatch = $repo->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos]);
                                if (!$nextMatch) {
                                    $nextMatch = $repo->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound]);
                                }
                            }
                            if ($nextMatch) {
                                if (($position % 2) === 0) {
                                    if (!$nextMatch->getEquipes()) $nextMatch->setEquipes($winner);
                                } else {
                                    if (!$nextMatch->getEquipeVisiteur()) $nextMatch->setEquipeVisiteur($winner);
                                }
                                $em->persist($nextMatch);
                            }

                            // losers in a losers match are eliminated — nothing to advance
                        }
                    }
                }
            }
        }

        $em->flush();

        $this->addFlash('success', 'Score sauvegardé.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
    }

    #[Route('/tournoi/{id}/reset-winner', name: 'app_tournoi_reset_winner', methods: ['POST'])]
    public function resetWinner(int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reset_winner_'.$id, $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $em = $doctrine->getManager();
        $tournoi = $doctrine->getRepository(\App\Entity\Tournoi::class)->find($id);

        if (!$tournoi) {
            $this->addFlash('error', 'Ressource introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        // Only creator or admin can reset winner
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut réinitialiser le gagnant.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $tournoi->setWinner(null);
        $em->persist($tournoi);
        $em->flush();

        $this->addFlash('success', 'Le gagnant du tournoi a été réinitialisé.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/tournoi/{id}/set-winner/{equipeId}', name: 'app_tournoi_set_winner', methods: ['POST'])]
    public function setWinner(int $id, int $equipeId, Request $request, ManagerRegistry $doctrine): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('set_winner_'.$id, $submittedToken)) {
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

        // Only creator or admin can set winner
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut désigner le gagnant.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
        }

        $tournoi->setWinner($equipe);
        $em->persist($tournoi);
        $em->flush();

        $this->addFlash('success', $equipe->getNom() . ' a été désigné gagnant du tournoi.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $id]);
    }

    #[Route('/rencontre/{id}/set-winner/{equipeId}', name: 'app_rencontre_set_winner', methods: ['POST'])]
    public function setMatchWinner(int $id, int $equipeId, Request $request, ManagerRegistry $doctrine): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('set_match_winner_'.$id, $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $request->request->get('tournoi_id', 0)]);
        }

        $em = $doctrine->getManager();
        $rencontre = $doctrine->getRepository(\App\Entity\Rencontre::class)->find($id);
        $equipe = $doctrine->getRepository(\App\Entity\Equipe::class)->find($equipeId);

        if (!$rencontre || !$equipe) {
            $this->addFlash('error', 'Ressource introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        $tournoi = $rencontre->getTournoi();

        // Only creator or admin can set match winner
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut désigner le gagnant.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        // Mark winner and mark match as completed
        $rencontre->setWinner($equipe);
        $rencontre->setStatus('completed');
        $em->persist($rencontre);

        // When a winner is set manually, we should also advance the winner in elimination brackets
        $tournoi = $rencontre->getTournoi();
        if (in_array($tournoi->getFormat(), ['elimination_simple', 'double_elimination'], true)) {
            // Determine loser
            $home = $rencontre->getEquipes();
            $away = $rencontre->getEquipeVisiteur();
            if ($home && $away) {
                if ($equipe === $home) {
                    $winner = $home;
                    $loser = $away;
                } else {
                    $winner = $away;
                    $loser = $home;
                }

                $currentRound = $rencontre->getRound() ?: 0;
                $position = $rencontre->getPosition() ?: 0;

                // Single-elimination: advance winner in the same winners flow
                if ($tournoi->getFormat() === 'elimination_simple') {
                    $nextRound = $currentRound + 1;
                    $targetPos = (int) floor($position / 2);
                    try {
                        $nextMatch = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos]);
                    } catch (\Doctrine\DBAL\Exception $e) {
                        $nextMatch = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound]);
                    }
                    if (isset($nextMatch) && $nextMatch) {
                        if (($position % 2) === 0) {
                            if (!$nextMatch->getEquipes()) {
                                $nextMatch->setEquipes($winner);
                            }
                        } else {
                            if (!$nextMatch->getEquipeVisiteur()) {
                                $nextMatch->setEquipeVisiteur($winner);
                            }
                        }
                        $em->persist($nextMatch);
                    }
                }

                // Double-elimination: advance winners, and place loser into losers' bracket
                if ($tournoi->getFormat() === 'double_elimination') {
                    if ($rencontre->getBracket() === 'winners') {
                        $nextRound = $currentRound + 1;
                        $targetPos = (int) floor($position / 2);
                        try {
                            $nextMatch = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos, 'bracket' => 'winners']);
                        } catch (\Doctrine\DBAL\Exception $e) {
                            $nextMatch = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos]);
                        }
                        if ($nextMatch) {
                            if (($position % 2) === 0) {
                                if (!$nextMatch->getEquipes()) $nextMatch->setEquipes($winner);
                            } else {
                                if (!$nextMatch->getEquipeVisiteur()) $nextMatch->setEquipeVisiteur($winner);
                            }
                            $em->persist($nextMatch);
                        }

                        // place loser into first available slot in losers round 1
                        if ($loser) {
                            try {
                                $loserTarget = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => 1, 'position' => $position, 'bracket' => 'losers']);
                            } catch (\Doctrine\DBAL\Exception $e) {
                                $loserTarget = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => 1, 'position' => $position]);
                                if (!$loserTarget) {
                                    $loserTarget = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => 1]);
                                }
                            }
                            if ($loserTarget) {
                                if (($position % 2) === 0) {
                                    if (!$loserTarget->getEquipes()) $loserTarget->setEquipes($loser);
                                } else {
                                    if (!$loserTarget->getEquipeVisiteur()) $loserTarget->setEquipeVisiteur($loser);
                                }
                                $em->persist($loserTarget);
                            }
                        }
                    } elseif ($rencontre->getBracket() === 'losers') {
                        // winners in losers bracket advance within losers rounds
                        $nextRound = $currentRound + 1;
                        $targetPos = (int) floor($position / 2);
                        try {
                            $nextMatch = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos, 'bracket' => 'losers']);
                        } catch (\Doctrine\DBAL\Exception $e) {
                            $nextMatch = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound, 'position' => $targetPos]);
                            if (!$nextMatch) {
                                $nextMatch = $doctrine->getRepository(\App\Entity\Rencontre::class)->findOneBy(['tournoi' => $tournoi, 'round' => $nextRound]);
                            }
                        }
                        if ($nextMatch) {
                            if (($position % 2) === 0) {
                                if (!$nextMatch->getEquipes()) $nextMatch->setEquipes($winner);
                            } else {
                                if (!$nextMatch->getEquipeVisiteur()) $nextMatch->setEquipeVisiteur($winner);
                            }
                            $em->persist($nextMatch);
                        }
                    }
                }
            }
        }

        $em->flush();

        $this->addFlash('success', 'Gagnant du match désigné.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
    }

    #[Route('/rencontre/{id}/reset-winner', name: 'app_rencontre_reset_winner', methods: ['POST'])]
    public function resetMatchWinner(int $id, Request $request, ManagerRegistry $doctrine): Response
    {
        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reset_match_winner_'.$id, $submittedToken)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $request->request->get('tournoi_id', 0)]);
        }

        $em = $doctrine->getManager();
        $rencontre = $doctrine->getRepository(\App\Entity\Rencontre::class)->find($id);

        if (!$rencontre) {
            $this->addFlash('error', 'Ressource introuvable.');
            return $this->redirectToRoute('app_tournois');
        }

        $tournoi = $rencontre->getTournoi();

        // Only creator or admin can set match winner
        if (!$this->getUser() || $tournoi->getCreator() === null || ($this->getUser() !== $tournoi->getCreator() && !$this->isGranted('ROLE_ADMIN'))) {
            $this->addFlash('error', 'Seul le créateur du tournoi peut désigner le gagnant.');
            return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
        }

        $rencontre->setWinner(null);
        // If we reset a match winner, mark the match as not completed so it can be re-decided
        $rencontre->setStatus('scheduled');
        $em->persist($rencontre);
        $em->flush();

        $this->addFlash('success', 'Gagnant du match réinitialisé.');
        return $this->redirectToRoute('app_tournoi_show', ['id' => $tournoi->getId()]);
    }
}
