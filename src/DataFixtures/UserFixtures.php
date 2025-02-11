<?php

namespace App\DataFixtures;

use App\Entity\Nutzer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * @author Ben Brooksnieder
 */
class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Salted password is "test"
        $admin = new Nutzer();
        $admin->SetCustom($name = 'Admin',
            $fullname = 'Testadmin',
            $email = 'admin@localhost',
            $role = ['ROLE_ADMIN'],
            $saltedpassword = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2'
        );
        $manager->persist($admin);
        $this->addReference('admin', $admin);

        // Salted password is "test"
        $user1 = new Nutzer();
        $user1->SetCustom($name = 'User',
            $fullname = 'Testuser',
            $email = 'user@localhost',
            $role = ['ROLE_USER'],
            $saltedpassword = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2'
        );
        $manager->persist($user1);
        $this->addReference('user1', $user1);

        // Salted password is "test"
        $user2 = new Nutzer();
        $user2->SetCustom($name = 'user2',
            $fullname = 'Testuser2',
            $email = 'user2@localhost',
            $role = ['ROLE_USER'],
            $saltedpassword = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2'
        );
        $manager->persist($user2);
        $this->addReference('user2', $user2);

        // Salted password is "test"
        $user3 = new Nutzer();
        $user3->SetCustom($name = 'Üser3',
            $fullname = 'Testuser3',
            $email = 'user3@localhost',
            $role = ['ROLE_USER'],
            $saltedpassword = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2'
        );
        $manager->persist($user3);
        $this->addReference('user3', $user3);

        // save
        $manager->flush();
    }
}
