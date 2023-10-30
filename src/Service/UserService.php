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

    public function addUser(
        string $address,
        string $added,
        string $locale,
        array  $locales,
        array  $events,
        string $theme,
        bool   $sensitive = true,
        bool   $yggdrasil = true,
        bool   $posters   = true,
        bool   $approved  = false,
        bool   $moderator = false,
        bool   $status    = true
    ): ?User
    {
        // Create new user
        $user = new User();

        $user->setAddress(
            $address
        );

        $user->setAdded(
            $added
        );

        $user->setApproved(
            $approved
        );

        $user->setModerator(
            $moderator
        );

        $user->setStatus(
            $status
        );

        $user->setLocale(
            $locale
        );

        $user->setLocales(
            $locales
        );

        $user->setTheme(
            $theme
        );

        $user->setEvents(
            $events
        );

        $user->setSensitive(
            $sensitive
        );

        $user->setYggdrasil(
            $yggdrasil
        );

        $user->setPosters(
            $posters
        );

        $this->entityManagerInterface->persist($user);
        $this->entityManagerInterface->flush();

        // Set initial user as approved & moderator
        if (1 === $user->getId())
        {
            $user->setApproved(true);
            $user->setModerator(true);
            $user->setSensitive(false);

            $this->entityManagerInterface->persist($user);
            $this->entityManagerInterface->flush();
        }

        // Return user data
        return $user;
    }

    public function getUser(int $userId): ?User
    {
        return $this->entityManagerInterface
                    ->getRepository(User::class)
                    ->find($userId);
    }

    public function findUserByAddress(string $address): ?User
    {
        return $this->entityManagerInterface
                    ->getRepository(User::class)
                    ->findOneBy(
                        [
                            'address' => $address
                        ]
                    );
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
                    ->findOneBy(
                        [
                            'userId'       => $userId,
                            'userIdTarget' => $userIdTarget
                        ]
                    );
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
    ): bool
    {
        if ($userStar = $this->findUserStar($userId, $userIdTarget))
        {
            $this->entityManagerInterface->remove($userStar);
            $this->entityManagerInterface->flush();

            return false;
        }

        else
        {
            $userStar = new UserStar();

            $userStar->setUserId($userId);
            $userStar->setUserIdTarget($userIdTarget);
            $userStar->setAdded($added);

            $this->entityManagerInterface->persist($userStar);
            $this->entityManagerInterface->flush();

            return true;
        }
    }

    public function toggleUserModerator(
        int $userId
    ): ?User
    {
        if ($user = $this->getUser($userId))
        {
            $user->setModerator(
                !$user->isModerator()
            );

            $this->entityManagerInterface->persist($user);
            $this->entityManagerInterface->flush();
        }

        return $user;
    }

    public function toggleUserStatus(
        int $userId
    ): ?User
    {
        if ($user = $this->getUser($userId))
        {
            $user->setStatus(
                !$user->isStatus()
            );

            $this->entityManagerInterface->persist($user);
            $this->entityManagerInterface->flush();
        }

        return $user;
    }

    public function toggleUserApproved(
        int $userId
    ): ?User
    {
        if ($user = $this->getUser($userId))
        {
            $user->setApproved(
                !$user->isApproved()
            );

            $this->entityManagerInterface->persist($user);
            $this->entityManagerInterface->flush();
        }

        return $user;
    }
}