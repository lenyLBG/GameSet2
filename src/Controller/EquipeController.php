<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Form\EquipeType;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/equipe')]
final class EquipeController extends AbstractController
{
    #[Route(name: 'app_equipe_index', methods: ['GET'])]
    public function index(EquipeRepository $equipeRepository): Response
    {
        return $this->render('equipe/index.html.twig', [
            'equipes' => $equipeRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_equipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $equipe = new Equipe();
        $form = $this->createForm(EquipeType::class, $equipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var \App\Entity\User $user */
            $user = $this->getUser();
            $equipe->addUser($user);
            
            $entityManager->persist($equipe);
            $entityManager->flush();

            $this->addFlash('success', 'Équipe créée avec succès !');
            return $this->redirectToRoute('app_profile', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipe/new.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_equipe_show', methods: ['GET'])]
    public function show(Equipe $equipe): Response
    {
        return $this->render('equipe/show.html.twig', [
            'equipe' => $equipe,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_equipe_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EquipeType::class, $equipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_equipe_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('equipe/edit.html.twig', [
            'equipe' => $equipe,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/ajax-edit', name: 'app_equipe_ajax_edit', methods: ['POST'])]
    public function ajaxEdit(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): JsonResponse
    {
        // simple AJAX handler to update basic equipe fields from the profile modal
        $id = $equipe->getId();
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('equipe_ajax_edit_' . $id, $token)) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $nom = trim((string) $request->request->get('nom', ''));
        $coach = trim((string) $request->request->get('coach', ''));
        $contact = trim((string) $request->request->get('contact', ''));
        $sport = trim((string) $request->request->get('sport', ''));

        if ($nom !== '') {
            $equipe->setNom($nom);
        }
        $equipe->setCoach($coach ?: null);
        $equipe->setContact($contact ?: null);
        $equipe->setSport($sport ?: null);

        $entityManager->flush();

        // prepare members list
        $members = [];
        foreach ($equipe->getUsers() as $u) {
            $members[] = trim($u->getPrenom() . ' ' . $u->getNom());
        }

        return new JsonResponse([
            'success' => true,
            'equipe' => [
                'id' => $id,
                'nom' => $equipe->getNom(),
                'coach' => $equipe->getCoach(),
                'contact' => $equipe->getContact(),
                'sport' => $equipe->getSport(),
                'members' => $members,
                'members_count' => count($members),
            ],
        ]);
    }

    #[Route('/{id}', name: 'app_equipe_delete', methods: ['POST'])]
    public function delete(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipe->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($equipe);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_equipe_index', [], Response::HTTP_SEE_OTHER);
    }
}
