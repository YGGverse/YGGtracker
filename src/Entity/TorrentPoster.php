<?php

namespace App\Entity;

use App\Repository\TorrentPosterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TorrentPosterRepository::class)]
class TorrentPoster
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

    #[ORM\Column]
    private ?bool $approved = null;

    #[ORM\Column(length: 32)]
    private ?string $md5file = null;

    #[ORM\Column(length: 255)]
    private ?string $position = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
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

    public function isApproved(): ?bool
    {
        return $this->approved;
    }

    public function setApproved(bool $approved): static
    {
        $this->approved = $approved;

        return $this;
    }

    public function getMd5file(): ?string
    {
        return $this->md5file;
    }

    public function setMd5file(string $md5file): static
    {
        $this->md5file = $md5file;

        return $this;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(string $position): static
    {
        $this->position = $position;

        return $this;
    }
}
