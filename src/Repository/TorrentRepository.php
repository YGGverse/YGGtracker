<?php

namespace App\Repository;

use App\Entity\Torrent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Torrent>
 *
 * @method Torrent|null find($id, $lockMode = null, $lockVersion = null)
 * @method Torrent|null findOneBy(array $criteria, array $orderBy = null)
 * @method Torrent[]    findAll()
 * @method Torrent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Torrent::class);
    }

    public function findTorrentsTotal(
        int   $userId,
        array $keywords,
        array $locales,
        ?bool $sensitive = null,
        ?bool $approved  = null,
        ?bool $status    = null,
        int   $limit  = 10,
        int   $offset = 0
    ): int
    {
        return $this->getTorrentsQueryByFilter(
            $userId,
            $keywords,
            $locales,
            $sensitive,
            $approved,
            $status,
        )->select('count(t.id)')
         ->getQuery()
         ->getSingleScalarResult();
    }

    public function findTorrents(
        int   $userId,
        array $keywords,
        array $locales,
        ?bool $sensitive = null,
        ?bool $approved  = null,
        ?bool $status    = null,
        int $limit  = 10,
        int $offset = 0
    ): array
    {
        return $this->getTorrentsQueryByFilter(
            $userId,
            $keywords,
            $locales,
            $sensitive,
            $approved,
            $status,
        )->setMaxResults($limit)
         ->setFirstResult($offset)
         ->orderBy('t.id', 'DESC') // same as t.added
         ->getQuery()
         ->getResult();
    }

    private function getTorrentsQueryByFilter(
        int   $userId,
        array $keywords,
        array $locales,
        ?bool $sensitive       = null,
        ?bool $approved        = null,
        ?bool $status          = null
    ): \Doctrine\ORM\QueryBuilder
    {
        $query = $this->createQueryBuilder('t');

        if ($keywords)
        {
            foreach ($keywords as $i => $keyword)
            {
                // Make query to the index case insensitive
                $keyword = mb_strtolower($keyword);

                // Init OR condition for each word form
                $orKeywords = $query->expr()->orX();

                $orKeywords->add("t.keywords LIKE :keyword{$i}");
                $query->setParameter(":keyword{$i}", "%{$keyword}%");

                // Generate  word forms for each transliteration locale #33
                foreach ($this->generateWordForms($keyword) as $j => $wordForm)
                {
                    $orKeywords->add("t.keywords LIKE :keyword{$i}{$j}");
                    $query->setParameter(":keyword{$i}{$j}", "%{$wordForm}%");
                }

                // Append AND condition
                $query->andWhere($orKeywords);
            }
        }

        if ($locales)
        {
            $orLocales = $query->expr()->orX();

            foreach ($locales as $i => $locale)
            {
                $orLocales->add("t.locales LIKE :locale{$i}");
                $orLocales->add("t.userId = :userId");

                $query->setParameter(":locale{$i}", "%{$locale}%");
                $query->setParameter('userId', $userId);
            }

            $query->andWhere($orLocales);
        }

        if (is_bool($sensitive))
        {
            $orSensitive = $query->expr()->orX();

            $orSensitive->add("t.sensitive = :sensitive");
            $orSensitive->add("t.userId = :userId");

            $query->setParameter('sensitive', $sensitive);
            $query->setParameter('userId', $userId);

            $query->andWhere($orSensitive);
        }

        if (is_bool($approved))
        {
            $orApproved = $query->expr()->orX();

            $orApproved->add("t.approved = :approved");
            $orApproved->add("t.userId = :userId");

            $query->setParameter('approved', $approved);
            $query->setParameter('userId', $userId);

            $query->andWhere($orApproved);
        }

        if (is_bool($status))
        {
            $orStatus = $query->expr()->orX();

            $orStatus->add("t.status = :status");
            $orStatus->add("t.userId = :userId");

            $query->setParameter('status', $status);
            $query->setParameter('userId', $userId);

            $query->andWhere($orStatus);
        }

        return $query;
    }

    // Word forms generator to improve search results
    // e.g. transliteration rules for latin filenames
    private function generateWordForms(
        string $keyword,
        // #33 supported locales:
        // https://github.com/ashtokalo/php-translit
        array  $transliteration = [
            'be',
            'bg',
            'el',
            'hy',
            'kk',
            'mk',
            'ru',
            'ka',
            'uk'
        ],
        // Additional char forms
        array $charForms =
        [
            'c' => 'k',
            'k' => 'c',
        ]
    ): array
    {
        $wordForms = [];

        // Apply transliteration
        foreach ($transliteration as $locale)
        {
            $wordForms[] = \ashtokalo\translit\Translit::object()->convert(
                $keyword,
                $locale
            );
        }

        // Apply char forms
        foreach ($wordForms as $wordForm)
        {
            foreach ($charForms as $from => $to)
            {
                $wordForms[] = str_replace(
                    $from,
                    $to,
                    $wordForm
                );
            }
        }

        // Remove duplicates
        return array_unique(
            $wordForms
        );
    }
}
