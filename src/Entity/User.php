<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column]
    private ?int $added = null;

    #[ORM\Column]
    private ?bool $moderator = null;

    #[ORM\Column]
    private ?bool $approved = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(length: 2)]
    private ?string $locale = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $locales = [];

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private array $events = [];

    #[ORM\Column(length: 255)]
    private ?string $theme = null;

    #[ORM\Column]
    private ?bool $sensitive = null;

    #[ORM\Column]
    private ?bool $yggdrasil = null;

    #[ORM\Column]
    private ?bool $posters = null;

    #[ORM\Column(type: Types::SIMPLE_ARRAY)]
    private ?array $categories = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

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

    public function isModerator(): ?bool
    {
        return $this->moderator;
    }

    public function setModerator(bool $moderator): static
    {
        $this->moderator = $moderator;

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

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

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

    public function getEvents(): array
    {
        return $this->events;
    }

    public function setEvents(array $events): static
    {
        $this->events = $events;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

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

    public function isYggdrasil(): ?bool
    {
        return $this->yggdrasil;
    }

    public function setYggdrasil(bool $yggdrasil): static
    {
        $this->yggdrasil = $yggdrasil;

        return $this;
    }

    public function isPosters(): ?bool
    {
        return $this->posters;
    }

    public function setPosters(bool $posters): static
    {
        $this->posters = $posters;

        return $this;
    }

    public function getCategories(): ?array
    {
        return $this->categories;
    }

    public function setCategories(?array $categories): static
    {
        $this->categories = $categories;

        return $this;
    }
}
