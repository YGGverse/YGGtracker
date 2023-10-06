<?php

namespace App\Repository;

use App\Entity\PageDescription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageDescription>
 *
 * @method PageDescription|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageDescription|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageDescription[]    findAll()
 * @method PageDescription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageDescriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageDescription::class);
    }
}
