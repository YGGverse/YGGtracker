<?php

namespace App\Entity;

use App\Repository\TorrentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TorrentRepository::class)]

class Torrent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column]
    private ?int $added = null;

    #[ORM\Column(nullable: true)]
    private ?int $scraped = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $locales = [];

    #[ORM\Column]
    private ?bool $sensitive = null;

    #[ORM\Column]
    private ?bool $approved = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(length: 32)]
    private ?string $md5file = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY, nullable: true)]
    private ?array $keywords = null;

    #[ORM\Column(nullable: true)]
    private ?int $seeders = null;

    #[ORM\Column(nullable: true)]
    private ?int $peers = null;

    #[ORM\Column(nullable: true)]
    private ?int $leechers = null;

    #[ORM\Column(nullable: true)]
    private ?int $torrentPosterId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

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

    public function getScraped(): ?int
    {
        return $this->scraped;
    }

    public function setScraped(int $scraped): static
    {
        $this->scraped = $scraped;

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

    public function getKeywords(): ?array
    {
        return $this->keywords;
    }

    public function setKeywords(?array $keywords): static
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }

    public function setLocales(array $locales): static
    {
        $this->locales = $locales;

        return $this;
    }

    public function isSensitive(): ?bool
    {
        return $this->sensitive;
    }

    public function setSensitive(bool $sensitive): static
    {
        $this->sensitive = $sensitive;

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

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSeeders(): ?int
    {
        return $this->seeders;
    }

    public function setSeeders(?int $seeders): static
    {
        $this->seeders = $seeders;

        return $this;
    }

    public function getPeers(): ?int
    {
        return $this->peers;
    }

    public function setPeers(?int $peers): static
    {
        $this->peers = $peers;

        return $this;
    }

    public function getLeechers(): ?int
    {
        return $this->leechers;
    }

    public function setLeechers(?int $leechers): static
    {
        $this->leechers = $leechers;

        return $this;
    }

    public function getTorrentPosterId(): ?int
    {
        return $this->torrentPosterId;
    }

    public function setTorrentPosterId(?int $torrentPosterId): static
    {
        $this->torrentPosterId = $torrentPosterId;

        return $this;
    }
}
