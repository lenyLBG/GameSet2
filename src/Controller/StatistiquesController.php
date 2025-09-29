<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StatistiquesController extends AbstractController
{
    #[Route('/statistique', name: 'app_statistiques')]
    public function index(): Response
    {
        return $this->render('statistiques/index.html.twig', [
            'controller_name' => 'StatistiquesController',
        ]);
    }
}
