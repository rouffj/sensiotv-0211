<?php

namespace App\EventSubscriber;

use App\Event\RegistrationSucceedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RegistrationSucceedSubscriber implements EventSubscriberInterface
{
    public function onRegistrationSucceedSendEmail(RegistrationSucceedEvent $event)
    {
        $user = $event->getUser();
        $email = [
            'from' => 'team@sensiotv.io',
            'to' => $user->getEmail(),
            'subject' => 'Bravo '.$user->getFirstName().', votre compte a été créé avec succès !',
            'body' => 'Bienvenue '.$user->getFirstName().' '.$user->getLastName().' dans la communauté SensioTV',
        ];
        dump($email);
    }

    public static function getSubscribedEvents()
    {
        return [
            'registration_succeed' => 'onRegistrationSucceedSendEmail',
        ];
    }
}
