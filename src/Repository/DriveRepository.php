<?php

namespace App\Repository;

use App\Entity\Drive;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Drive>
 *
 * @method Drive|null find($id, $lockMode = null, $lockVersion = null)
 * @method Drive|null findOneBy(array $criteria, array $orderBy = null)
 * @method Drive[]    findAll()
 * @method Drive[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DriveRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Drive::class);
    }
}
