<?php

/**
 * Repository class for Fall.
 *
 * @author Ben Brooksnieder
 */

namespace App\Repository;

use App\Entity\Fall;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FallRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fall::class);
    }

    /**
     * Find all open cases.
     *
     * @param int|null $limit optional limit max number of results
     *
     * @return Fall[]
     */
    public function findAllOpen(?int $limit = null): array
    {
        // create query builder
        $builder = $this->createQueryBuilder('f')
            ->where('f.istAktiv = 1')
            ->orderBy('f.zeitstempel_beginn', 'DESC');

        if (null !== $limit) {
            $builder->setMaxResults($limit);
        }

        // get and run query
        $query = $builder->getQuery();

        return $query->execute();
    }
}
