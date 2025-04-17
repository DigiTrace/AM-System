<?php
namespace App\Purger;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Purger\ORMPurgerInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Ben Brooksnieder
 */
class Purger implements ORMPurgerInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    function setEntityManager(EntityManagerInterface $em): void {
        $this->em = $em;
    }

    public function purge(): void
    {
        $this->em->getConnection()->exec('SET foreign_key_checks = 0');
        $OrmPurger = new ORMPurger($this->em);
        $OrmPurger->setEntityManager($this->em);
        $OrmPurger->purge();
        $this->em->getConnection()->exec('SET foreign_key_checks = 1');
    }
}