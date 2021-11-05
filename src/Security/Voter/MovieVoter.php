<?php

namespace App\Security\Voter;

use App\Entity\Movie;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class MovieVoter extends Voter
{
    protected function supports(string $attribute, $subject): bool
    {
        // replace with your own logic
        // https://symfony.com/doc/current/security/voters.html
        return in_array($attribute, ['MOVIE_SHOW'])
            && $subject instanceof \App\Entity\Movie;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        /** @var Movie $movie */
        $movie = $subject;
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        // ... (check conditions and return true to grant permission) ...
        $vote = false;
        switch ($attribute) {
            case 'MOVIE_SHOW':
                if ($user->getBirthDate() < $movie->getReleaseDate()) {
                    $vote = true;
                }
                break;
            case 'MOVIE_DELETE':
                break;
        }

        return $vote;
    }
}
