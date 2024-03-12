<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProvider extends EntityUserProvider
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function loadUserByIdentifier(string $email): UserInterface
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('LOWER(u.email) = :email')
            ->setParameter('email', strtolower($email))
            ->getQuery()
            ->getOneOrNullResult();

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Invalid user class "%s".', get_class($user)));
        }

        return $user;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}