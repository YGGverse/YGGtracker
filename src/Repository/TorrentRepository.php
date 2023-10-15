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
        array $keywords,
        array $locales,
        ?bool $sensitive = null,
        ?bool $approved = null,
        int $limit  = 0,
        int $offset = 10
    ): int
    {
        return $this->getTorrentsQueryByFilter(
            $keywords,
            $locales,
            $sensitive,
            $approved,
        )->select('count(t.id)')
         ->getQuery()
         ->getSingleScalarResult();
    }

    public function findTorrents(
        array $keywords,
        array $locales,
        ?bool $sensitive = null,
        ?bool $approved = null,
        int $limit  = 0,
        int $offset = 10
    ): array
    {
        return $this->getTorrentsQueryByFilter(
            $keywords,
            $locales,
            $sensitive,
            $approved,
        )->setMaxResults($limit)
                     ->setFirstResult($offset)
                     ->orderBy('t.id', 'DESC') // same as t.added
                     ->getQuery()
                     ->getResult();
    }

    private function getTorrentsQueryByFilter(
        array $keywords,
        array $locales,
        ?bool $sensitive = null,
        ?bool $approved = null,
    ): \Doctrine\ORM\QueryBuilder
    {
        $query = $this->createQueryBuilder('t');

        if ($keywords) // @TODO ANY or DTS
        {
            $andX = $query->expr()->andX();

            foreach ($keywords as $i => $keyword)
            {
                $keyword = mb_strtolower($keyword); // all keywords stored in lowercase

                $andX->add("t.keywords LIKE :keyword{$i}");
                $query->setParameter(":keyword{$i}", "%{$keyword}%");
            }

            $query->andWhere($andX);
        }

        if ($locales) // @TODO ANY or DTS
        {
            //$orX = $query->expr()->orX();
            $orX = $query->expr()->orX();

            foreach ($locales as $i => $locale)
            {
                $orX->add("t.locales LIKE :locale{$i}");

                $query->setParameter(":locale{$i}", "%{$locale}%");
            }

            $query->andWhere($orX);
        }

        if (is_bool($sensitive))
        {
            $query->andWhere('t.sensitive = :sensitive')
                  ->setParameter('sensitive', $sensitive);
        }

        if (is_bool($approved))
        {
            $query->andWhere('t.approved = :approved')
                  ->setParameter('approved', $approved);
        }

        return $query;
    }
}
