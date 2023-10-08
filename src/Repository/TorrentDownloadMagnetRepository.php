<?php

namespace App\Repository;

use App\Entity\TorrentDownloadMagnet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TorrentDownloadMagnet>
 *
 * @method TorrentDownloadMagnet|null find($id, $lockMode = null, $lockVersion = null)
 * @method TorrentDownloadMagnet|null findOneBy(array $criteria, array $orderBy = null)
 * @method TorrentDownloadMagnet[]    findAll()
 * @method TorrentDownloadMagnet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentDownloadMagnetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TorrentDownloadMagnet::class);
    }

    public function findTorrentDownloadMagnet(
        int $torrentId,
        int $userId
    ): ?TorrentDownloadMagnet
    {
        return $this->createQueryBuilder('tdm')
            ->where('tdm.torrentId = :torrentId')
            ->andWhere('tdm.userId = :userId')
            ->setParameter('torrentId', $torrentId)
            ->setParameter('userId', $userId)
            ->orderBy('tdm.id', 'DESC') // same to ts.added
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findTorrentDownloadMagnetsTotalByTorrentId(
        int $torrentId
    ): int
    {
        return $this->createQueryBuilder('tdm')
            ->select('count(tdm.id)')
            ->where('tdm.torrentId = :torrentId')
            ->setParameter('torrentId', $torrentId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
