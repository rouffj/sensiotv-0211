<?php

namespace App\Controller;

use App\Entity\Movie;
use App\OmdbApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/movie", name="movie_", methods={"GET"})
 */
class MovieController extends AbstractController
{
    private OmdbApi $omdbApi;

    private EntityManagerInterface $entityManager;

    public function __construct(OmdbApi $omdbApi, EntityManagerInterface $entityManager)
    {
        $this->omdbApi = $omdbApi;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/search", name="search")
     */
    public function search(Request $request): Response
    {
        $keyword = $request->query->get('keyword', 'Sky');
        $search = $this->omdbApi->requestAllBySearch($keyword);

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

    /**
     * @Route("/{imdbId}/import", requirements={"imdbId": "\w\w\d+"})
     */
    public function import(string $imdbId): Response
    {
        $movieData = $this->omdbApi->requestOneById($imdbId);

        if (!$movieData) {
            $this->addFlash('error', 'Movie not found.');

            throw $this->createNotFoundException();
        }

        $movie = Movie::fromApi($movieData);

        $this->entityManager->persist($movie);
        $this->entityManager->flush();

        return $this->redirectToRoute('movie_latest');
    }
}
