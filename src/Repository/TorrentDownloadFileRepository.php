<?php

namespace App\Repository;

use App\Entity\TorrentDownloadFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TorrentDownloadFile>
 *
 * @method TorrentDownloadFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method TorrentDownloadFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method TorrentDownloadFile[]    findAll()
 * @method TorrentDownloadFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentDownloadFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TorrentDownloadFile::class);
    }

    public function findTorrentDownloadFile(
        int $torrentId,
        int $userId
    ): ?TorrentDownloadFile
    {
        return $this->createQueryBuilder('tdf')
            ->where('tdf.torrentId = :torrentId')
            ->andWhere('tdf.userId = :userId')
            ->setParameter('torrentId', $torrentId)
            ->setParameter('userId', $userId)
            ->orderBy('tdf.id', 'DESC') // same to ts.added
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findTorrentDownloadFilesTotalByTorrentId(
        int $torrentId
    ): int
    {
        return $this->createQueryBuilder('tdf')
            ->select('count(tdf.id)')
            ->where('tdf.torrentId = :torrentId')
            ->setParameter('torrentId', $torrentId)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
