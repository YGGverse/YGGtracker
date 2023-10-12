<?php

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 *
 * @method Activity|null find($id, $lockMode = null, $lockVersion = null)
 * @method Activity|null findOneBy(array $criteria, array $orderBy = null)
 * @method Activity[]    findAll()
 * @method Activity[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function findActivitiesTotal(
        array $whitelist
    ): int
    {
        return $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->where('a.event IN (:event)')
            ->setParameter(':event', $whitelist)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findActivitiesTotalByUserId(
        int $userId,
        array $whitelist
    ): int
    {
        return $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->where('a.userId = :userId')
            ->andWhere('a.event IN (:event)')
            ->setParameter(':userId', $userId)
            ->setParameter(':event', $whitelist)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function findActivitiesTotalByTorrentId(
        int $torrentId,
        array $whitelist
    ): int
    {
        return $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->where('a.torrentId = :torrentId')
            ->andWhere('a.event IN (:event)')
            ->setParameter(':torrentId', $torrentId)
            ->setParameter(':event', $whitelist)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
