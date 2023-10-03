<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserService
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private ParameterBagInterface $parameterBagInterface;

    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $parameterBagInterface)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $entityManager->getRepository(User::class);
        $this->parameterBagInterface = $parameterBagInterface;
    }

    public function init(string $address): User
    {
        // Return existing user
        if ($result = $this->userRepository->findOneByAddressField($address))
        {
            return $result;
        }

        // Create new user
        $user = new User();

        $user->setAddress($address);
        $user->setAdded(time());
        $user->setApproved(false);
        $user->setModerator(false);
        $user->setStatus(true);
        $user->setLocale(
            $this->parameterBagInterface->get('app.locale')
        );
        $user->setLocales(
            explode('|', $this->parameterBagInterface->get('app.locales'))
        );

        $this->save($user);

        // Return user data
        return $user;
    }

    public function get(int $id): ?User
    {
        return $this->userRepository->findOneByIdField($id);
    }

    public function save(User $user) : void
    {
        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}