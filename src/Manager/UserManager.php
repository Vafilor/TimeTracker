<?php

declare(strict_types=1);

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UserManager
{
    private UserRepository $userRepository;
    private UserPasswordEncoderInterface $passwordEncoder;
    private EntityManagerInterface $entityManager;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordEncoderInterface $userPasswordEncoder,
        EntityManagerInterface $objectManager) {
        $this->userRepository = $userRepository;
        $this->passwordEncoder = $userPasswordEncoder;
        $this->entityManager = $objectManager;
    }

    public function createUser(string $email, string $username, string $password) {
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);

        // encode the plain password
        $user->setPassword(
            $this->passwordEncoder->encodePassword(
                $user,
                $password
            )
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}