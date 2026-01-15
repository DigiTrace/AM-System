<?php

namespace App\Repository;

use App\Entity\Fall;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for cases (Fall).
 *
 * @author Ben Brooksnieder
 */
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
        $builder = $this->createQueryBuilder('c')
            ->where('c.istAktiv = 1')
            ->orderBy('c.zeitstempel_beginn', 'DESC');

        if (null !== $limit) {
            $builder->setMaxResults($limit);
        }

        // get and run query
        $query = $builder->getQuery();

        return $query->execute();
    }

    /**
     * Simple search for cases by description or case id.
     *
     * @param mixed    $search Search input
     * @param int|null $limit  Optional limit
     *
     * @return Fall[]
     */
    public function findBySimpleSearch(mixed $search, ?int $limit): array
    {
        $builder = $this->createQueryBuilder('c')
            ->where('c.beschreibung like :search')
            ->orWhere('c.case_id like :search')
            ->orderBy('c.zeitstempel_beginn', 'DESC')
            ->setParameter('search', "%{$search}%")
        ;

        if (null !== $limit) {
            $builder->setMaxResults($limit);
        }

        $query = $builder->getQuery();

        return $query->getResult();
    }
}
