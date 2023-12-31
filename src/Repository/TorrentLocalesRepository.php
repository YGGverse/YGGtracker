<?php

namespace App\Repository;

use App\Entity\TorrentLocales;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TorrentLocales>
 *
 * @method TorrentLocales|null find($id, $lockMode = null, $lockVersion = null)
 * @method TorrentLocales|null findOneBy(array $criteria, array $orderBy = null)
 * @method TorrentLocales[]    findAll()
 * @method TorrentLocales[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentLocalesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TorrentLocales::class);
    }
}
