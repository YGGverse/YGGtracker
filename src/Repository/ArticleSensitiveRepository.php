<?php

namespace App\Repository;

use App\Entity\ArticleSensitive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleSensitive>
 *
 * @method ArticleSensitive|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleSensitive|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleSensitive[]    findAll()
 * @method ArticleSensitive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleSensitiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleSensitive::class);
    }
}
