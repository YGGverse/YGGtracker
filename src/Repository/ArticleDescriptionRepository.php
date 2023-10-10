<?php

namespace App\Repository;

use App\Entity\ArticleDescription;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleDescription>
 *
 * @method ArticleDescription|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleDescription|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleDescription[]    findAll()
 * @method ArticleDescription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleDescriptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleDescription::class);
    }
}
