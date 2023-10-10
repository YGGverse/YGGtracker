<?php

namespace App\Repository;

use App\Entity\Torrent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Torrent>
 *
 * @method Torrent|null find($id, $lockMode = null, $lockVersion = null)
 * @method Torrent|null findOneBy(array $criteria, array $orderBy = null)
 * @method Torrent[]    findAll()
 * @method Torrent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TorrentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Torrent::class);
    }

    public function searchByKeywords(
        array $keywords
    ): ?array
    {
        $query = $this->createQueryBuilder('t');

        foreach ($keywords as $keyword)
        {
            $query->orWhere('t.keywords LIKE :query')
                  ->setParameter('query', "%{$keyword}%");
        }

        return $query->orderBy('t.id', 'ASC') // same as t.added
                     ->getQuery()
                     ->getResult();
    }
}
