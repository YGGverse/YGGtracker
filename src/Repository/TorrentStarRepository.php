<?php

namespace App\Repository;

use App\Entity\TorrentStar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TorrentStar>
 *
 * @method TorrentStar|null find($id, $lockMode = null, $lockVersion = null)
 * @method TorrentStar|null findOneBy(array $criteria, array $orderBy = null)
 * @method TorrentStar[]    findAll()
 * @method TorrentStar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentStarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TorrentStar::class);
    }

    public function findTorrentStar(
        int $torrentId,
        int $userId
    ): ?TorrentStar
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

    public function findTorrentStarsTotalByTorrentId(
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
