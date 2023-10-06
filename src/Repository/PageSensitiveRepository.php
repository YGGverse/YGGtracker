<?php

namespace App\Repository;

use App\Entity\PageSensitive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageSensitive>
 *
 * @method PageSensitive|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageSensitive|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageSensitive[]    findAll()
 * @method PageSensitive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageSensitiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageSensitive::class);
    }
}
