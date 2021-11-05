<?php

namespace App\Controller\Api;

use App\Entity\Movie;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use http\Exception\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * CRUD:
 *
 * POST /api/users
 * GET /api/users
 * GET /api/users/:id
 * PATCH /api/users/:id
 * DELETE /api/users/:id
 *
 * @Route("/api/users", name="api_users_", defaults={"_format": "json"})
 */
class UserController extends AbstractController
{
    /**
     * @var SerializerInterface
     */
    private $serializer;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var ValidatorInterface
     */
    private $validator;

    public function __construct(SerializerInterface $serializer, EntityManagerInterface $entityManager, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->entityManager = $entityManager;
        $this->validator = $validator;
    }

    /**
     * @Route("", name="list", methods="GET")
     */
    public function list(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        dump($users);
        $usersAsJson = $this->serializer->serialize($users, 'json', [
            'actionType' => 'list',
            // Solution 1: sur l'affichage des propriétés
            //AbstractNormalizer::IGNORED_ATTRIBUTES => ['reviews'],
            AbstractNormalizer::GROUPS => ['user_read', '*'],
            'circular_reference_handler' => function($object, $format, $context) {
                if ($object instanceof User) {
                    return '/api/users/'. $object->getId();
                } else if ($object instanceof Movie) {
                    return '/api/movies/'. $object->getId();
                }
            },
        ]);

        return JsonResponse::fromJsonString($usersAsJson);
    }

    /**
     * Param converters
     *
     * @Route("/{id}", name="read", methods="GET")
     */
    public function read(User $user): Response
    {
        $json = $this->serializer->serialize($user, 'json');

        return JsonResponse::fromJsonString($json);
    }

    /**
     * {"firstName": "", "lastName": "", "email": "...", "phone": "", "password": "..."}
     *
     * @Route("", name="create", methods="POST")
     */
    public function create(Request $request): Response
    {
        try {
            $user = $this->createOrUpdateEntityFromApi($request);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::fromJsonString($e->getMessage());
        }

        return JsonResponse::fromJsonString($this->serializer->serialize($user, 'json'), 201);
    }

    /**
     * {"firstName": "", "lastName": "", ...}
     *
     * @Route("/{id}", name="update", methods="PATCH")
     */
    public function update(Request $request, User $user): Response
    {
        try {
            $updatedUser = $this->createOrUpdateEntityFromApi($request, $user);
        } catch (\InvalidArgumentException $e) {
            return JsonResponse::fromJsonString($e->getMessage());
        }

        return JsonResponse::fromJsonString($this->serializer->serialize($updatedUser, 'json'));
    }

    /**
     * Add this method into a dedicated class
     */
    public function createOrUpdateEntityFromApi(Request $request, object $entity = null): object
    {
        $body = $request->getContent();

        $options = [];
        if ($entity) {
            $className = get_class($entity);
            $options = [
                AbstractNormalizer::OBJECT_TO_POPULATE => $entity,
            ];
        } else {
            // @TODO A améliorer
            $className = User::class;
        }

        $serializedEntity = $this->serializer->deserialize($body, $className, 'json', $options);
        $errors = $this->validator->validate($serializedEntity);

        if (count($errors) > 0) {
            $errorsAsJson = $this->serializer->serialize($errors, 'json');
            throw new \InvalidArgumentException($errorsAsJson);
        }

        dump($body, $entity, $errors);
        if (!$entity) {
            $this->entityManager->persist($serializedEntity);
        }

        $this->entityManager->flush();

        return $serializedEntity;
    }
}