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

    public function findLast(int $start = 0, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC') // same to a.added
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findLastByApprovedField(bool $approved, int $start = 0, int $limit = 10): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.id', 'DESC') // same to a.added
            ->where('a.approved = :approved')
            ->setParameter('approved', $approved)
            ->setFirstResult($start)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }
}
