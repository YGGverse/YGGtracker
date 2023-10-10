<?php

namespace App\Service;

use App\Entity\Article;
use App\Entity\ArticleTitle;
use App\Entity\ArticleDescription;
use App\Entity\ArticleTorrents;
use App\Entity\ArticleSensitive;

use App\Repository\ArticleRepository;
use App\Repository\ArticleTitleRepository;
use App\Repository\ArticleDescriptionRepository;
use App\Repository\ArticleSensitiveRepository;
use App\Repository\ArticleTorrentsRepository;

use Doctrine\ORM\EntityManagerInterface;

class ArticleService
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
    ): ?Article
    {
        $article = $this->addArticle();

        if (!empty($title))
        {
            $articleTitle = $this->addArticleTitle(
                $article->getId(),
                $userId,
                $added,
                $locale,
                $title,
                $approved
            );
        }

        if (!empty($description))
        {
            $articleDescription = $this->addArticleDescription(
                $article->getId(),
                $userId,
                $added,
                $locale,
                $description,
                $approved
            );
        }

        if (!empty($torrents))
        {
            $articleTorrents = $this->addArticleTorrents(
                $article->getId(),
                $userId,
                $added,
                $locale,
                $torrents,
                $approved
            );
        }

        // @TODO
        $articleSensitive = $this->addArticleSensitive(
            $article->getId(),
            $userId,
            $added,
            $locale,
            $description,
            $approved
        );

        return $article;
    }

    public function addArticle(): ?Article
    {
        $article = new Article();

        $this->entityManager->persist($article);
        $this->entityManager->flush();

        return $article;
    }

    public function addArticleTitle(
        int $articleId,
        int $userId,
        int $added,
        string $locale,
        string $value,
        bool $approved
    ): ?ArticleTitle
    {
        $articleTitle = new ArticleTitle();

        $articleTitle->setArticleId($articleId);
        $articleTitle->setUserId($userId);
        $articleTitle->setLocale($locale);
        $articleTitle->setValue($value);
        $articleTitle->setAdded($added);
        $articleTitle->setApproved($approved);

        $this->entityManager->persist($articleTitle);
        $this->entityManager->flush();

        return $articleTitle;
    }

    public function addArticleDescription(
        int $articleId,
        int $userId,
        int $added,
        string $locale,
        string $value,
        bool $approved
    ): ?ArticleDescription
    {
        $articleDescription = new ArticleDescription();

        $articleDescription->setArticleId($articleId);
        $articleDescription->setUserId($userId);
        $articleDescription->setAdded($added);
        $articleDescription->setLocale($locale);
        $articleDescription->setValue($value);
        $articleDescription->setApproved($approved);

        $this->entityManager->persist($articleDescription);
        $this->entityManager->flush();

        return $articleDescription;
    }

    public function addArticleTorrents(
        int $articleId,
        int $userId,
        int $added,
        array $torrentsId,
        bool $approved
    ): ?ArticleTorrents
    {
        $articleTorrents = new ArticleTorrents();

        $articleTorrents->setArticleId($articleId);
        $articleTorrents->setUserId($userId);
        $articleTorrents->setAdded($added);
        $articleTorrents->setTorrentsId($torrentsId);
        $articleTorrents->setApproved($approved);

        $this->entityManager->persist($articleTorrents);
        $this->entityManager->flush();

        return $articleTorrents;
    }

    public function addArticleSensitive(
        int $articleId,
        int $userId,
        int $added,
        string $locale,
        string $value,
        bool $approved
    ): ?ArticleSensitive
    {
        $articleSensitive = new ArticleSensitive();

        $articleSensitive->setArticleId($articleId);
        $articleSensitive->setUserId($userId);
        $articleSensitive->setAdded($added);
        $articleSensitive->setLocale($locale);
        $articleSensitive->setValue($value);
        $articleSensitive->setApproved($approved);

        $this->entityManager->persist($articleSensitive);
        $this->entityManager->flush();

        return $articleSensitive;
    }
}