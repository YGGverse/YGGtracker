<?php

namespace App\Repository;

use App\Entity\PageTorrents;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PageTorrents>
 *
 * @method PageTorrents|null find($id, $lockMode = null, $lockVersion = null)
 * @method PageTorrents|null findOneBy(array $criteria, array $orderBy = null)
 * @method PageTorrents[]    findAll()
 * @method PageTorrents[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PageTorrentsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PageTorrents::class);
    }
}
