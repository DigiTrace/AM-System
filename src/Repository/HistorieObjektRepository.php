<?php

namespace App\Repository;

use App\Entity\HistorieObjekt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistorieObjekt>
 *
 * @method HistorieObjekt|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistorieObjekt|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistorieObjekt[]    findAll()
 * @method HistorieObjekt[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistorieObjektRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistorieObjekt::class);
    }

//    /**
//     * @return HistorieObjekt[] Returns an array of HistorieObjekt objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?HistorieObjekt
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
