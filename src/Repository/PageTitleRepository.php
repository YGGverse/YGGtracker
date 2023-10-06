<?php

namespace App\Repository;

use App\Entity\PageTitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageTitle>
 *
 * @method PageTitle|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageTitle|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageTitle[]    findAll()
 * @method PageTitle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageTitle::class);
    }
}
