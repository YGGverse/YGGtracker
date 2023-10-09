<?php

namespace App\Repository;

use App\Entity\UserStar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserStar>
 *
 * @method UserStar|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserStar|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserStar[]    findAll()
 * @method UserStar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserStarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserStar::class);
    }

    public function findUserStar(
        int $userId,
        int $userIdTarget
    ): ?UserStar
    {
        return $this->createQueryBuilder('us')
            ->where('us.userId = :userId')
            ->andWhere('us.userIdTarget = :userIdTarget')
            ->setParameter('userId', $userId)
            ->setParameter('userIdTarget', $userIdTarget)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findUserStarsTotalByUserIdTarget(
        int $userIdTarget
    ): int
    {
        return $this->createQueryBuilder('us')
            ->select('count(us.userId)')
            ->where('us.userIdTarget = :userIdTarget')
            ->setParameter('userIdTarget', $userIdTarget)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
