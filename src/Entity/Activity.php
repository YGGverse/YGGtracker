<?php

namespace App\Entity;

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $event = null;

    // Event codes

    /// User
    public const EVENT_USER_ADD                         = 1000;

    public const EVENT_USER_APPROVE_ADD                 = 1200;
    public const EVENT_USER_APPROVE_DELETE              = 1201;

    public const EVENT_USER_MODERATOR_ADD               = 1300;
    public const EVENT_USER_MODERATOR_DELETE            = 1301;

    public const EVENT_USER_STATUS_ADD                  = 1400;
    public const EVENT_USER_STATUS_DELETE               = 1401;

    public const EVENT_USER_STAR_ADD                    = 1500;
    public const EVENT_USER_STAR_DELETE                 = 1501;

    /// Torrent
    public const EVENT_TORRENT_ADD                      = 2000;

    public const EVENT_TORRENT_APPROVE_ADD              = 1100;
    public const EVENT_TORRENT_APPROVE_DELETE           = 1101;

    public const EVENT_TORRENT_LOCALES_ADD              = 2200;
    public const EVENT_TORRENT_LOCALES_DELETE           = 2201;
    public const EVENT_TORRENT_LOCALES_APPROVE_ADD      = 2210;
    public const EVENT_TORRENT_LOCALES_APPROVE_DELETE   = 2211;

    public const EVENT_TORRENT_SENSITIVE_ADD            = 2300;
    public const EVENT_TORRENT_SENSITIVE_DELETE         = 2301;
    public const EVENT_TORRENT_SENSITIVE_APPROVE_ADD    = 2310;
    public const EVENT_TORRENT_SENSITIVE_APPROVE_DELETE = 2311;

    public const EVENT_TORRENT_STAR_ADD                 = 2400;
    public const EVENT_TORRENT_STAR_DELETE              = 2401;

    public const EVENT_TORRENT_DOWNLOAD_FILE_ADD        = 2500;

    public const EVENT_TORRENT_DOWNLOAD_MAGNET_ADD      = 2600;

    public const EVENT_TORRENT_WANTED_ADD               = 2700;

    // ...

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(nullable: true)]
    private ?int $torrentId = null;

    #[ORM\Column]
    private ?int $added = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $data = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getEvent(): ?int
    {
        return $this->event;
    }

    public function setEvent(int $event): static
    {
        $this->event = $event;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getTorrentId(): ?int
    {
        return $this->torrentId;
    }

    public function setTorrentId(?int $torrentId): static
    {
        $this->torrentId = $torrentId;

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

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
