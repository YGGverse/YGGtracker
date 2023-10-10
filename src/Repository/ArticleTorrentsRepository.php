<?php

namespace App\Repository;

use App\Entity\ArticleTorrents;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ArticleTorrents>
 *
 * @method ArticleTorrents|null find($id, $lockMode = null, $lockVersion = null)
 * @method ArticleTorrents|null findOneBy(array $criteria, array $orderBy = null)
 * @method ArticleTorrents[]    findAll()
 * @method ArticleTorrents[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ArticleTorrentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ArticleTorrents::class);
    }
}
