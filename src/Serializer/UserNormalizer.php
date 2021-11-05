<?php

namespace App\Serializer;

use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

class UserNormalizer implements ContextAwareNormalizerInterface
{
    private $normalizer;

    public function __construct(ObjectNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    public function normalize($user, string $format = null, array $context = [])
    {
        $userAsArray = $this->normalizer->normalize($user, $format, $context);
        dump($userAsArray, $context);

        if (isset($context['actionType']) && 'list' === $context['actionType']) {
            $newUser['firstName'] = $userAsArray['firstName'];
            $newUser['email'] = $userAsArray['email'];
        } else {
            $newUser = $userAsArray;
        }

        return $newUser;
    }

    public function supportsNormalization($data, string $format = null, array $context = [])
    {
        return $data instanceof User;
    }
}