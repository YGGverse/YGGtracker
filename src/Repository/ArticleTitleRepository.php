<?php

namespace App\Repository;

use App\Entity\ArticleTitle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleTitle>
 *
 * @method ArticleTitle|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleTitle|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleTitle[]    findAll()
 * @method ArticleTitle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleTitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleTitle::class);
    }
}
