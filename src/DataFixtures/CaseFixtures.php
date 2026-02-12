<?php

namespace App\DataFixtures;

use App\Entity\CaseFile;
use App\Enum\CaseSecrecy;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Loads cases for dev purposes.
 *
 * @author Ben Brooksnieder
 */
class CaseFixtures extends Fixture
{
    public const CASE_REFERENCE = 'dt-fall-';

    public function load(ObjectManager $manager): void
    {
        // config for all objekte
        $config = [
            [
                'active' => true,
                'case_id' => 'XIVv2',
                'dos' => CaseSecrecy::Confidential,
                'desc' => '(TEST)Computersabotage',
                'timestamp' => date_create(),
            ],
            [
                'active' => true,
                'case_id' => 'TLG',
                'dos' => CaseSecrecy::Confidential,
                'desc' => '(TEST)Einbruch im Hochsicherheitstrakt beim HIER BEKANNTE FIRMA EINTRAGEN. Laptop mit HIER WICHTIGE DATENBESTAND EINFÜGEN Daten entwendet',
                'timestamp' => date_create(),
            ],
            [
                'active' => true,
                'case_id' => 'Müller/c1',
                'dos' => CaseSecrecy::Confidential,
                'desc' => '(TEST)Auf seinen privaten Rechner wurde eine Bitcoinsoftware per Malware installiert',
                'timestamp' => date_create(),
            ],
            [
                'active' => true,
                'case_id' => '78/98',
                'dos' => CaseSecrecy::Confidential,
                'desc' => '(TEST)Verdacht auf Besitz von KiPo',
                'timestamp' => date_create(),
            ],
            [
                'active' => true,
                'case_id' => 'Schmidt AG',
                'dos' => CaseSecrecy::Confidential,
                'desc' => '(TEST)Pentest des Front Webservers',
                'timestamp' => date_create(),
            ],
        ];

        foreach ($config as $id => $entry) {
            // add case
            $case = new CaseFile();
            $case->setActive($entry['active']);
            $case->setCaseId($entry['case_id']);
            $case->setSecrecy($entry['dos']);
            $case->setDescription($entry['desc']);
            $case->setOpenedOn($entry['timestamp']);
            $manager->persist($case);
            $this->addReference(self::CASE_REFERENCE.$id, $case);
        }

        // save all to db
        $manager->flush();
    }
}
