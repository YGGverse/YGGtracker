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

    public function getTorrent(int $id): ?Torrent
    {
        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function getTorrentScrapeQueue(): ?Torrent
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.scraped', 'ASC') // same to ts.added
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
