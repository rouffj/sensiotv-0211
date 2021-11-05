<?php

namespace App\Controller;

use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\RegistrationSucceedEvent;
use Symfony\Contracts\EventDispatcher\Event;

class UserController extends AbstractController
{
    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, EventDispatcherInterface $eventDispatcher): Response
    {
        $form = $this->createForm(UserType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $eventDispatcher->dispatch(new RegistrationSucceedEvent($user), 'registration_succeed');
            dump($user);
        }

        // Avatar image should minified + send to AWS S3 as asynchronous task.
        $eventDispatcher->addListener('kernel.terminate', function(Event $e) {
            dump($e);
            dump('helloo from terminate');
            sleep(4);
        });

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}