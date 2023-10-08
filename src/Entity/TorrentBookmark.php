<?php

namespace App\Entity;

use App\Repository\TorrentBookmarkRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TorrentBookmarkRepository::class)]
class TorrentBookmark
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $torrentId = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column]
    private ?int $added = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTorrentId(): ?int
    {
        return $this->torrentId;
    }

    public function setTorrentId(int $torrentId): static
    {
        $this->torrentId = $torrentId;

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
