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
    public const EVENT_USER_ADD                         = 10000;

    public const EVENT_USER_APPROVE_ADD                 = 10200;
    public const EVENT_USER_APPROVE_DELETE              = 10210;

    public const EVENT_USER_MODERATOR_ADD               = 10300;
    public const EVENT_USER_MODERATOR_DELETE            = 10310;

    public const EVENT_USER_STATUS_ADD                  = 10400;
    public const EVENT_USER_STATUS_DELETE               = 10410;

    public const EVENT_USER_STAR_ADD                    = 10500;
    public const EVENT_USER_STAR_DELETE                 = 10510;

    /// Torrent
    public const EVENT_TORRENT_ADD                      = 20000;

    public const EVENT_TORRENT_LOCALES_ADD              = 20100;
    public const EVENT_TORRENT_LOCALES_DELETE           = 20101;
    public const EVENT_TORRENT_LOCALES_APPROVE_ADD      = 20110;
    public const EVENT_TORRENT_LOCALES_APPROVE_DELETE   = 20111;

    public const EVENT_TORRENT_SENSITIVE_ADD            = 20200;
    public const EVENT_TORRENT_SENSITIVE_DELETE         = 20201;
    public const EVENT_TORRENT_SENSITIVE_APPROVE_ADD    = 20210;
    public const EVENT_TORRENT_SENSITIVE_APPROVE_DELETE = 20211;

    public const EVENT_TORRENT_DOWNLOAD_FILE_ADD        = 20300;

    public const EVENT_TORRENT_DOWNLOAD_MAGNET_ADD      = 20400;

    /// Article
    public const EVENT_ARTICLE_ADD                      = 30000;
    // ...

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(nullable: true)]
    private ?int $articleId = null;

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

    public function getArticleId(): ?int
    {
        return $this->articleId;
    }

    public function setArticleId(int $articleId): static
    {
        $this->articleId = $articleId;

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
