<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Entity\Review;
use App\OmdbApi;
use App\Repository\MovieRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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
    public function latest(MovieRepository $movieRepository): Response
    {
        $movies = $movieRepository->findBy([], ['id' => 'DESC']);

        return $this->render('movie/latest.html.twig', [
            'movies' => $movies,
        ]);
    }

    /**
     * @Route("/{id}", name="show", requirements={"id": "\d+"})
     */
    public function show(Movie $movie, Request $request): Response
    {
        $this->denyAccessUnlessGranted('MOVIE_SHOW', $movie);

        return $this->render('movie/show.html.twig');
    }

    /**
     * @Route("/add_comment/{movieId}/{userId}/{body}", name="add_comment")
     */
    public function addReview(int $movieId, int $userId, string $body, MovieRepository $movieRepo, UserRepository $userRepo): Response
    {
        $movie = $movieRepo->find($movieId);
        $user = $userRepo->find($userId);

        $review = new Review();
        $review
            ->setMovie($movie)
            ->setUser($user)
            ->setBody($body)
            ->setRating(4)
        ;
        dump($review);

        $this->entityManager->persist($review);
        $this->entityManager->flush();
    }

    /**
     * @Route("/{imdbId}/import", requirements={"imdbId": "\w\w\d+"})
     * @ IsGranted("ROLE_ADMIN", message="You should be an admin to import a movie from the API")
     */
    public function import(string $imdbId): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            // allow to display the error message when redirected to login page
            $message = 'You should be an admin to import a movie from the API';
            $this->addFlash('error', $message);

            throw new AccessDeniedException($message);
        }

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
