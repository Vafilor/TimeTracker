<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserManager
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $userPasswordHasher,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createUser(string $email, string $username, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);

        // encode the plain password
        $user->setPassword(
            $this->userPasswordHasher->hashPassword(
                $user,
                $password
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function changePassword(string $username, string $newPassword)
    {
        $user = $this->userRepository->findOneByOrException(['username' => $username]);

        $user->setPassword(
            $this->userPasswordHasher->encodePassword(
                $user,
                $newPassword
            )
        );

        $this->entityManager->flush();
    }

    public function userExistsForUsername(string $username): bool
    {
        $user = $this->userRepository->findOneBy(['username' => $username]);

        return !is_null($user);
    }
}
