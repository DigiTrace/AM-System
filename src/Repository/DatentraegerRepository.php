<?php

namespace App\Repository;

use App\Entity\Datentraeger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Datentraeger>
 *
 * @method Datentraeger|null find($id, $lockMode = null, $lockVersion = null)
 * @method Datentraeger|null findOneBy(array $criteria, array $orderBy = null)
 * @method Datentraeger[]    findAll()
 * @method Datentraeger[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DatentraegerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Datentraeger::class);
    }

//    /**
//     * @return Datentraeger[] Returns an array of Datentraeger objects
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

//    public function findOneBySomeField($value): ?Datentraeger
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
