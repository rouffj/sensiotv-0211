<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api")
 */
class MovieController extends AbstractController
{
    /**
     * @Route("/movies", name="api_movie")
     */
    public function index(): Response
    {
        $movies = [
            ['title' => 'Retour vers le future 1'],
            ['title' => 'Retour vers le future 2'],
            ['title' => 'Retour vers le future 3'],
            ['title' => 'Matrix'],
        ];

        return $this->json($movies);
    }
}
