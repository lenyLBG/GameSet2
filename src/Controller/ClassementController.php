<?php

namespace App\Controller;

use App\Entity\Classement;
use App\Form\ClassementType;
use App\Repository\ClassementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/classement')]
final class ClassementController extends AbstractController
{
    #[Route(name: 'app_classement_index', methods: ['GET'])]
    public function index(ClassementRepository $classementRepository): Response
    {
        return $this->render('classement/index.html.twig', [
            'classements' => $classementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_classement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $classement = new Classement();
        $form = $this->createForm(ClassementType::class, $classement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($classement);
            $entityManager->flush();

            return $this->redirectToRoute('app_classement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('classement/new.html.twig', [
            'classement' => $classement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_classement_show', methods: ['GET'])]
    public function show(Classement $classement): Response
    {
        return $this->render('classement/show.html.twig', [
            'classement' => $classement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_classement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Classement $classement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClassementType::class, $classement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_classement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('classement/edit.html.twig', [
            'classement' => $classement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_classement_delete', methods: ['POST'])]
    public function delete(Request $request, Classement $classement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$classement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($classement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_classement_index', [], Response::HTTP_SEE_OTHER);
    }
}
