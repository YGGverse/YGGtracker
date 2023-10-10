<?php

namespace App\Entity;

use App\Repository\ArticleTorrentsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticleTorrentsRepository::class)]
class ArticleTorrents
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $articleId = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $torrentsId = [];

    #[ORM\Column]
    private ?int $added = null;

    #[ORM\Column]
    private ?bool $approved = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getArticleId(): ?int
    {
        return $this->articleId;
    }

    public function setArticleId(int $articleId): static
    {
        $this->articleId = $articleId;

        return $this;
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

    public function getTorrentsId(): array
    {
        return $this->torrentsId;
    }

    public function setTorrentsId(array $torrentsId): static
    {
        $this->torrentsId = $torrentsId;

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

    public function isApproved(): ?bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): static
    {
        $this->approved = $approved;

        return $this;
    }
}
