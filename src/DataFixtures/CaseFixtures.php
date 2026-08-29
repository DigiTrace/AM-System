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
    public const CASE_REFERENCE = 'dt-case-';

    public function load(ObjectManager $manager): void
    {
        // config for all objekte
        $config = [
            [
                'active' => true,
                'caseid' => 'XIVv2',
                'secrecy' => CaseSecrecy::Confidential,
                'description' => '(TEST)Computersabotage',
                'openedOn' => date_create(),
            ],
            [
                'active' => true,
                'caseid' => 'TLG',
                'secrecy' => CaseSecrecy::Confidential,
                'description' => '(TEST)Einbruch im Hochsicherheitstrakt beim HIER BEKANNTE FIRMA EINTRAGEN. Laptop mit HIER WICHTIGE DATENBESTAND EINFÜGEN Daten entwendet',
                'openedOn' => date_create(),
            ],
            [
                'active' => true,
                'caseid' => 'Müller/c1',
                'secrecy' => CaseSecrecy::Confidential,
                'description' => '(TEST)Auf seinen privaten Rechner wurde eine Bitcoinsoftware per Malware installiert',
                'openedOn' => date_create(),
            ],
            [
                'active' => true,
                'caseid' => '78/98',
                'secrecy' => CaseSecrecy::Confidential,
                'description' => '(TEST)Verdacht auf Besitz von KiPo',
                'openedOn' => date_create(),
            ],
            [
                'active' => true,
                'caseid' => 'Schmidt AG',
                'secrecy' => CaseSecrecy::Confidential,
                'description' => '(TEST)Pentest des Front Webservers',
                'openedOn' => date_create(),
            ],
        ];

        foreach ($config as $id => $entry) {
            // add case
            $case = new CaseFile();
            $case->setActive($entry['active']);
            $case->setCaseId($entry['caseid']);
            $case->setSecrecy($entry['secrecy']);
            $case->setDescription($entry['description']);
            $case->setOpenedOn($entry['openedOn']);
            $manager->persist($case);
            $this->addReference(self::CASE_REFERENCE.$id, $case);
        }

        // save all to db
        $manager->flush();
    }
}
