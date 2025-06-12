<?php
namespace App\Purger;


use Doctrine\Common\DataFixtures\Purger\PurgerInterface;
use Doctrine\Bundle\FixturesBundle\Purger\PurgerFactory as IPurgerFactory;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Ben Brooksnieder
 */
class PurgerFactory implements IPurgerFactory
{
    public function createForEntityManager(?string $emName, EntityManagerInterface $em, array $excluded = [], bool $purgeWithTruncate = false) : PurgerInterface
    {
        return new Purger($em);
    }
}