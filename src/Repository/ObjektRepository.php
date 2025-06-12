<?php

/**
 * Repository class for Objekt.
 *
 * @author Ben Brooksnieder
 */

namespace App\Repository;

use App\Entity\Objekt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\UserInterface;

class ObjektRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Objekt::class);
    }

    /**
     * Find all objects reserved by `user`.
     *
     * @param int|null $limit optional limit max number of results
     *
     * @return Objekt[]
     */
    public function findAllReservedByUser(int|UserInterface $user, ?int $limit = null): array
    {
        // get user id
        if (!is_int($user)) {
            $user = $user->getId();
        }

        // create query builder
        $builder = $this->createQueryBuilder('o')
            ->where('o.reserviert_von = :user')
            ->setParameter('user', $user)
            ->orderBy('o.zeitstempel', 'DESC');

        if (null !== $limit) {
            $builder->setMaxResults($limit);
        }

        // get and run query
        $query = $builder->getQuery();

        return $query->execute();
    }
}
