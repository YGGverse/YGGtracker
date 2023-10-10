<?php

namespace App\Entity;

use App\Repository\TorrentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

// @TODO #[ORM\Index(columns: ['keywords'], name: 'keywords_idx', flags: ['fulltext'])]
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

    #[ORM\Column]
    private ?bool $approved = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $keywords = null;

    #[ORM\Column(nullable: true)]
    private ?int $seeders = null;

    #[ORM\Column(nullable: true)]
    private ?int $peers = null;

    #[ORM\Column(nullable: true)]
    private ?int $leechers = null;

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

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function setKeywords(?string $keywords): static
    {
        $this->keywords = $keywords;

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
}
