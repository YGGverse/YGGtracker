<?php

namespace App\Service;

use App\Entity\Page;
use App\Entity\PageTitle;
use App\Entity\PageDescription;
use App\Entity\PageTorrents;
use App\Entity\PageSensitive;

use App\Repository\PageRepository;
use App\Repository\PageTitleRepository;
use App\Repository\PageDescriptionRepository;
use App\Repository\PageSensitiveRepository;
use App\Repository\PageTorrentsRepository;

use Doctrine\ORM\EntityManagerInterface;

class PageService
{
    private EntityManagerInterface $entityManager;
    private ParameterBagInterface $parameterBagInterface;

    public function __construct(
        EntityManagerInterface $entityManager,
    )
    {
        $this->entityManager = $entityManager;
    }

    public function submit(
        int $added,
        int $userId,
        string $locale,
        string $title,
        string $description,
        array $torrents,
        bool $sensitive,
        bool $approved
    ): ?Page
    {
        $page = $this->addPage();

        if (!empty($title))
        {
            $pageTitle = $this->addPageTitle(
                $page->getId(),
                $userId,
                $added,
                $locale,
                $title,
                $approved
            );
        }

        if (!empty($description))
        {
            $pageDescription = $this->addPageDescription(
                $page->getId(),
                $userId,
                $added,
                $locale,
                $description,
                $approved
            );
        }

        if (!empty($torrents))
        {
            $pageTorrents = $this->addPageTorrents(
                $page->getId(),
                $userId,
                $added,
                $locale,
                $torrents,
                $approved
            );
        }

        // @TODO
        $pageSensitive = $this->addPageSensitive(
            $page->getId(),
            $userId,
            $added,
            $locale,
            $description,
            $approved
        );

        return $page;
    }

    public function addPage(): ?Page
    {
        $page = new Page();

        $this->entityManager->persist($page);
        $this->entityManager->flush();

        return $page;
    }

    public function addPageTitle(
        int $pageId,
        int $userId,
        int $added,
        string $locale,
        string $value,
        bool $approved
    ): ?PageTitle
    {
        $pageTitle = new PageTitle();

        $pageTitle->setPageId($pageId);
        $pageTitle->setUserId($userId);
        $pageTitle->setLocale($locale);
        $pageTitle->setValue($value);
        $pageTitle->setAdded($added);
        $pageTitle->setApproved($approved);

        $this->entityManager->persist($pageTitle);
        $this->entityManager->flush();

        return $pageTitle;
    }

    public function addPageDescription(
        int $pageId,
        int $userId,
        int $added,
        string $locale,
        string $value,
        bool $approved
    ): ?PageDescription
    {
        $pageDescription = new PageDescription();

        $pageDescription->setPageId($pageId);
        $pageDescription->setUserId($userId);
        $pageDescription->setAdded($added);
        $pageDescription->setLocale($locale);
        $pageDescription->setValue($value);
        $pageDescription->setApproved($approved);

        $this->entityManager->persist($pageDescription);
        $this->entityManager->flush();

        return $pageDescription;
    }

    public function addPageTorrents(
        int $pageId,
        int $userId,
        int $added,
        array $torrentsId,
        bool $approved
    ): ?PageTorrents
    {
        $pageTorrents = new PageTorrents();

        $pageTorrents->setPageId($pageId);
        $pageTorrents->setUserId($userId);
        $pageTorrents->setAdded($added);
        $pageTorrents->setTorrentsId($torrentsId);
        $pageTorrents->setApproved($approved);

        $this->entityManager->persist($pageTorrents);
        $this->entityManager->flush();

        return $pageTorrents;
    }

    public function addPageSensitive(
        int $pageId,
        int $userId,
        int $added,
        string $locale,
        string $value,
        bool $approved
    ): ?PageSensitive
    {
        $pageSensitive = new PageSensitive();

        $pageSensitive->setPageId($pageId);
        $pageSensitive->setUserId($userId);
        $pageSensitive->setAdded($added);
        $pageSensitive->setLocale($locale);
        $pageSensitive->setValue($value);
        $pageSensitive->setApproved($approved);

        $this->entityManager->persist($pageSensitive);
        $this->entityManager->flush();

        return $pageSensitive;
    }
}