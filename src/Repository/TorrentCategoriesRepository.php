<?php

namespace App\Repository;

use App\Entity\TorrentCategories;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TorrentCategories>
 *
 * @method TorrentCategories|null find($id, $lockMode = null, $lockVersion = null)
 * @method TorrentCategories|null findOneBy(array $criteria, array $orderBy = null)
 * @method TorrentCategories[]    findAll()
 * @method TorrentCategories[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentCategoriesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TorrentCategories::class);
    }
}
