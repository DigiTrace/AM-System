<?php

namespace App\Repository;

use App\Entity\CaseFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository class for cases (Fall).
 *
 * @author Ben Brooksnieder
 */
class CaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CaseFile::class);
    }

    /**
     * Find all open cases.
     *
     * @param int|null $limit optional limit max number of results
     *
     * @return CaseFile[]
     */
    public function findAllOpen(?int $limit = null): array
    {
        // create query builder
        $builder = $this->createQueryBuilder('c')
            ->where('c.active = 1')
            ->orderBy('c.openedOn', 'DESC');

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
     * @return CaseFile[]
     */
    public function findBySimpleSearch(mixed $search, ?int $limit): array
    {
        $builder = $this->createQueryBuilder('c')
            ->where('c.description like :search')
            ->orWhere('c.caseId like :search')
            ->orderBy('c.openedOn', 'DESC')
            ->setParameter('search', "%{$search}%")
        ;

        if (null !== $limit) {
            $builder->setMaxResults($limit);
        }

        $query = $builder->getQuery();

        return $query->getResult();
    }
}
