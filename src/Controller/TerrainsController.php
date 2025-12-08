<?php

namespace App\Controller;

use App\Entity\Terrains;
use App\Form\TerrainsType;
use App\Repository\TerrainsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/terrains')]
final class TerrainsController extends AbstractController
{
    #[Route(name: 'app_terrains_index', methods: ['GET'])]
    public function index(TerrainsRepository $terrainsRepository): Response
    {
        return $this->render('terrains/index.html.twig', [
            'terrains' => $terrainsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_terrains_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $terrain = new Terrains();
        $form = $this->createForm(TerrainsType::class, $terrain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($terrain);
            $entityManager->flush();

            return $this->redirectToRoute('app_terrains_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('terrains/new.html.twig', [
            'terrain' => $terrain,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_terrains_show', methods: ['GET'])]
    public function show(Terrains $terrain): Response
    {
        return $this->render('terrains/show.html.twig', [
            'terrain' => $terrain,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_terrains_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Terrains $terrain, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TerrainsType::class, $terrain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_terrains_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('terrains/edit.html.twig', [
            'terrain' => $terrain,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_terrains_delete', methods: ['POST'])]
    public function delete(Request $request, Terrains $terrain, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$terrain->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($terrain);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_terrains_index', [], Response::HTTP_SEE_OTHER);
    }
}
