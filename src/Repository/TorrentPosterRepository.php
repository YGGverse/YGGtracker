<?php

namespace App\Repository;

use App\Entity\TorrentPoster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TorrentPoster>
 *
 * @method TorrentPoster|null find($id, $lockMode = null, $lockVersion = null)
 * @method TorrentPoster|null findOneBy(array $criteria, array $orderBy = null)
 * @method TorrentPoster[]    findAll()
 * @method TorrentPoster[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentPosterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TorrentPoster::class);
    }
}
