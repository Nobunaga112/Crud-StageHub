<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LouiswebController extends AbstractController
{
    #[Route('/louisweb', name: 'app_louisweb')]
    public function index(): Response
    {
        return $this->render('louisweb/index.html.twig', [
            'controller_name' => 'LouiswebController',
        ]);
    }
}
