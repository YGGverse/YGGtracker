<?php

namespace App\Entity;

use App\Repository\UserStarRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserStarRepository::class)]
class UserStar
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column]
    private ?int $userIdTarget = null;

    #[ORM\Column]
    private ?int $added = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getUserIdTarget(): ?int
    {
        return $this->userIdTarget;
    }

    public function setUserIdTarget(int $userIdTarget): static
    {
        $this->userIdTarget = $userIdTarget;

        return $this;
    }

    public function getAdded(): ?int
    {
        return $this->added;
    }

    public function setAdded(int $added): static
    {
        $this->added = $added;

        return $this;
    }
}
