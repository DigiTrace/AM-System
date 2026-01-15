<?php

namespace App\Repository;

use App\Entity\AssetHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AssetHistory>
 *
 * @method AssetHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method AssetHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method AssetHistory[]    findAll()
 * @method AssetHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AssetHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AssetHistory::class);
    }

//    /**
//     * @return AssetHistory[] Returns an array of AssetHistory objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AssetHistory
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
