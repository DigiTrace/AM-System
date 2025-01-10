<?php

namespace App\DataFixtures;

use App\Entity\Fall;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Loads cases for dev purposes.
 *
 * @author Ben Brooksnieder
 */
class FallFixtures extends Fixture
{
    public const FALL_REFERENCE = 'dt-fall-';

    public function load(ObjectManager $manager): void
    {
        // config for all objekte
        $config = [
            [
                'active' => true,
                'case_id' => 'XIVv2',
                'dos' => Fall::DEGREE_OF_SECRECY_CONFIDENTIAL,
                'desc' => '(TEST)Computersabotage',
                'timestamp' => date_create(),
            ],
            [
                'active' => true,
                'case_id' => 'TLG',
                'dos' => Fall::DEGREE_OF_SECRECY_CONFIDENTIAL,
                'desc' => '(TEST)Einbruch im Hochsicherheitstrakt beim HIER BEKANNTE FIRMA EINTRAGEN. Laptop mit HIER WICHTIGE DATENBESTAND EINFÜGEN Daten entwendet',
                'timestamp' => date_create(),
            ],
            [
                'active' => true,
                'case_id' => 'Müller/c1',
                'dos' => Fall::DEGREE_OF_SECRECY_CONFIDENTIAL,
                'desc' => '(TEST)Auf seinen privaten Rechner wurde eine Bitcoinsoftware per Malware installiert',
                'timestamp' => date_create(),
            ],
            [
                'active' => true,
                'case_id' => '78/98',
                'dos' => Fall::DEGREE_OF_SECRECY_CONFIDENTIAL,
                'desc' => '(TEST)Verdacht auf Besitz von KiPo',
                'timestamp' => date_create(),
            ],
            [
                'active' => true,
                'case_id' => 'Schmidt AG',
                'dos' => Fall::DEGREE_OF_SECRECY_CONFIDENTIAL,
                'desc' => '(TEST)Pentest des Front Webservers',
                'timestamp' => date_create(),
            ],
        ];

        foreach ($config as $id => $entry) {
            // add case
            $case = new Fall();
            $case->setistAktiv($entry['active']);
            $case->setCaseId($entry['case_id']);
            $case->setDOS($entry['dos']);
            $case->setBeschreibung($entry['desc']);
            $case->setZeitstempel($entry['timestamp']);
            $manager->persist($case);
            $this->addReference(self::FALL_REFERENCE.$id, $case);
        }

        // save all to db
        $manager->flush();
    }
}
