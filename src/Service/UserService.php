<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserStar;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserService
{
    private EntityManagerInterface $entityManagerInterface;
    private ParameterBagInterface $parameterBagInterface;

    public function __construct(
        EntityManagerInterface $entityManagerInterface,
        ParameterBagInterface $parameterBagInterface
    )
    {
        $this->entityManagerInterface = $entityManagerInterface;
        $this->parameterBagInterface = $parameterBagInterface;
    }

    public function init(string $address): User
    {
        // Return existing user
        if ($result = $this->entityManagerInterface
                           ->getRepository(User::class)
                           ->findOneByAddressField($address))
        {
            return $result;
        }

        // Create new user
        $user = new User();

        $user->setAddress(
            $address
        );

        $user->setAdded(
            time()
        );

        $user->setApproved(
            false
        );

        $user->setModerator(
            false
        );

        $user->setStatus(
            true
        );

        $user->setLocale(
            $this->parameterBagInterface->get('app.locale')
        );

        $user->setLocales(
            explode('|', $this->parameterBagInterface->get('app.locales'))
        );

        $user->setTheme(
            $this->parameterBagInterface->get('app.theme')
        );

        $user->setSensitive(
            $this->parameterBagInterface->get('app.sensitive')
        );

        $this->save($user);

        // Set initial user as approved & moderator
        if (1 === $user->getId())
        {
            $user->setApproved(true);
            $user->setModerator(true);
            $user->setSensitive(false);
            $this->save($user);
        }

        // Return user data
        return $user;
    }

    public function getUser(int $userId): ?User
    {
        return $this->entityManagerInterface
                    ->getRepository(User::class)
                    ->getUser($userId);
    }

    public function identicon(
        mixed  $value,
        int    $size = 16,
        array  $style =
        [
            'backgroundColor' => 'rgba(255, 255, 255, 0)',
            'padding' => 0
        ],
        string $format = 'webp'
    ): string
    {
        $identicon = new \Jdenticon\Identicon();

        $identicon->setValue($value);
        $identicon->setSize($size);
        $identicon->setStyle($style);

        return $identicon->getImageDataUri($format);
    }

    public function save(User $user) : void // @TODO delete
    {
        $this->entityManagerInterface->persist($user);
        $this->entityManagerInterface->flush();
    }

    // User star
    public function findUserStar(
        int $userId,
        int $userIdTarget
    ): ?UserStar
    {
        return $this->entityManagerInterface
                    ->getRepository(UserStar::class)
                    ->findUserStar($userId, $userIdTarget);
    }

    public function findUserStarsTotalByUserIdTarget(int $torrentId): int
    {
        return $this->entityManagerInterface
                    ->getRepository(UserStar::class)
                    ->findUserStarsTotalByUserIdTarget($torrentId);
    }

    public function toggleUserStar(
        int $userId,
        int $userIdTarget,
        int $added
    ): void
    {
        if ($userStar = $this->findUserStar($userId, $userIdTarget))
        {
            $this->entityManagerInterface->remove($userStar);
            $this->entityManagerInterface->flush();
        }

        else
        {
            $userStar = new UserStar();

            $userStar->setUserId($userId);
            $userStar->setUserIdTarget($userIdTarget);
            $userStar->setAdded($added);

            $this->entityManagerInterface->persist($userStar);
            $this->entityManagerInterface->flush();
        }
    }

    public function toggleUserModerator(
        int $userId
    ): void
    {
        if ($user = $this->getUser($userId))
        {
            $user->setModerator(
                !$user->isModerator()
            );

            $this->entityManagerInterface->persist($user);
            $this->entityManagerInterface->flush();
        }
    }

    public function toggleUserStatus(
        int $userId
    ): void
    {
        if ($user = $this->getUser($userId))
        {
            $user->setStatus(
                !$user->isStatus()
            );

            $this->entityManagerInterface->persist($user);
            $this->entityManagerInterface->flush();
        }
    }

    public function toggleUserApproved(
        int $userId
    ): void
    {
        if ($user = $this->getUser($userId))
        {
            $user->setApproved(
                !$user->isApproved()
            );

            $this->entityManagerInterface->persist($user);
            $this->entityManagerInterface->flush();
        }
    }
}