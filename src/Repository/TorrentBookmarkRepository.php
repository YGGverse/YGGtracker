<?php

namespace App\Repository;

use App\Entity\TorrentBookmark;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TorrentBookmark>
 *
 * @method TorrentBookmark|null find($id, $lockMode = null, $lockVersion = null)
 * @method TorrentBookmark|null findOneBy(array $criteria, array $orderBy = null)
 * @method TorrentBookmark[]    findAll()
 * @method TorrentBookmark[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentBookmarkRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TorrentBookmark::class);
    }

    public function findTorrentBookmark(
        int $torrentId,
        int $userId
    ): ?TorrentBookmark
    {
        return $this->createQueryBuilder('tb')
            ->where('tb.torrentId = :torrentId')
            ->andWhere('tb.userId = :userId')
            ->setParameter('torrentId', $torrentId)
            ->setParameter('userId', $userId)
            ->orderBy('tb.id', 'DESC') // same to ts.added
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findTorrentBookmarksTotalByTorrentId(
        int $torrentId
    ): int
    {
        return $this->createQueryBuilder('tb')
            ->select('count(tb.id)')
            ->where('tb.torrentId = :torrentId')
            ->setParameter('torrentId', $torrentId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
