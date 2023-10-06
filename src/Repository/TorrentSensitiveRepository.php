<?php

namespace App\Repository;

use App\Entity\TorrentSensitive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TorrentSensitive>
 *
 * @method TorrentSensitive|null find($id, $lockMode = null, $lockVersion = null)
 * @method TorrentSensitive|null findOneBy(array $criteria, array $orderBy = null)
 * @method TorrentSensitive[]    findAll()
 * @method TorrentSensitive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentSensitiveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TorrentSensitive::class);
    }
}
