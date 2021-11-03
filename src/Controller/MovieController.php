<?php

namespace App\Controller;

use App\OmdbApi;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/movie", name="movie_", methods={"GET"})
 */
class MovieController extends AbstractController
{
    /**
     * @Route("/search", name="search")
     */
    public function search(Request $request, OmdbApi $omdbApi): Response
    {
        $keyword = $request->query->get('keyword', 'Sky');
        $search = $omdbApi->requestAllBySearch($keyword);

        dump($search);

        return $this->render('movie/search.html.twig', [
            'keyword' => $keyword,
            'movies' => $search['Search'],
            'nb_movies' => $search['totalResults'],
        ]);
    }

    /**
     * @Route("/latest", name="latest")
     */
    public function latest(): Response
    {
        return $this->render('movie/latest.html.twig');
    }

    /**
     * @Route("/{id}", name="show", requirements={"id": "\d+"})
     */
    public function show(int $id, Request $request): Response
    {
        return $this->render('movie/show.html.twig', [
            'id' => $id,
        ]);
    }
}
