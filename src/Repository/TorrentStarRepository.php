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

    public function findTorrentStarsTotalByTorrentId(
        int $torrentId
    ): int
    {
        return $this->createQueryBuilder('ts')
            ->select('count(ts.id)')
            ->where('ts.torrentId = :torrentId')
            ->setParameter('torrentId', $torrentId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
