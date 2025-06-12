<?php

namespace App\DataFixtures;

use App\Entity\Datentraeger;
use App\Entity\HistorieObjekt;
use App\Entity\Nutzer;
use App\Entity\Objekt;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Loads objekts and datentraeger for dev purposes.
 *
 * @author Ben Brooksnieder
 */
class ObjektFixtures extends Fixture implements DependentFixtureInterface
{
    public const OBJEKT_REFERENCE = 'dt-objekt-';
    public const DATENTRAEGER_REFERENCE = 'dt-datentraeger-';
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $user1 = $this->getReference('user1', Nutzer::class);
        $user3 = $this->getReference('user3', Nutzer::class);

        // config for all objekte
        $config = [
            [
                'barcode_id' => 'DTAS00001',
                'nutzer' => $user1,
                'name' => '(TEST)Hitachi Festplatte',
                'verwendung' => '(TEST)Sind gelöschte Beweise drauf',
                'kategorie_id' => 0,
                'kategorie' => 'category.exhibit',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTHW00007',
                'nutzer' => $user1,
                'name' => '(TEST)Thinkpad e330',
                'verwendung' => '(TEST)Ediscovery',
                'kategorie_id' => 1,
                'kategorie' => 'category.equipment',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTHD00021',
                'nutzer' => $user1,
                'name' => '(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte',
                'verwendung' => '(TEST)Wird für Ein Asservat benötigt',
                'kategorie_id' => 3,
                'kategorie' => 'category.hdd',
                'status_id' => 0,
                'hdd' => [
                    'bauart' => '',
                    'formfaktor' => '',
                    'groesse' => 0,
                    'groessealt' => 0,
                    'modell' => '',
                    'hersteller' => '',
                    'sn' => '',
                    'pn' => '',
                    'anschluss' => '',
                ],
            ],
            [
                'barcode_id' => 'DTHW00001',
                'nutzer' => $user1,
                'name' => '(TEST)Encase Koffer mit speziffischen Inhalt',
                'verwendung' => '',
                'kategorie_id' => 1,
                'kategorie' => 'category.equipment',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTHD00022',
                'nutzer' => $user1,
                'name' => '(TEST)Toshiba 500GB',
                'verwendung' => '(TEST)Austauschplatte Für den Server',
                'kategorie_id' => 3,
                'kategorie' => 'category.hdd',
                'status_id' => 0,
                'hdd' => [
                    'bauart' => 'intern',
                    'formfaktor' => '3,5',
                    'groesse' => 500,
                    'groessealt' => 500,
                    'modell' => 'Modell 1',
                    'hersteller' => 'Toshiba',
                    'sn' => '89437809756B',
                    'pn' => 'GHII9',
                    'anschluss' => 'SATA',
                ],
            ],
            [
                'barcode_id' => 'DTHD00023',
                'nutzer' => $user1,
                'name' => '(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte',
                'verwendung' => '(TEST)Notfallplatte für Forensikkoffer',
                'kategorie_id' => 3,
                'kategorie' => 'category.hdd',
                'status_id' => 0,
                'hdd' => [
                    'bauart' => 'extern',
                    'formfaktor' => '2,5',
                    'groesse' => 2000,
                    'groessealt' => 2000,
                    'modell' => 'T00JH9II',
                    'hersteller' => 'Toshiba',
                    'sn' => '89437809756C',
                    'pn' => 'GHII9',
                    'anschluss' => 'USB',
                ],
            ],
            [
                'barcode_id' => 'DTHW00002',
                'nutzer' => $user1,
                'name' => '(TEST)Schrank',
                'verwendung' => '(TEST)Wird zum Lagern von Asservaten gebraucht',
                'kategorie_id' => 2,
                'kategorie' => 'category.container',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTHW00003',
                'nutzer' => $user3,
                'name' => '(TEST)Papierbox',
                'verwendung' => '(TEST)Wird zum Lagern von HDDs gebraucht',
                'kategorie_id' => 2,
                'kategorie' => 'category.container',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTHW00004',
                'nutzer' => $user1,
                'name' => '(TEST)Peli Case',
                'verwendung' => '(TEST)Für Mobilen Einsatz',
                'kategorie_id' => 2,
                'kategorie' => 'category.container',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTHW00005',
                'nutzer' => $user3,
                'name' => '(TEST)Pappkarton',
                'verwendung' => '(TEST)Zwecks Dringlichkeit in das System eingetragen, nicht im Regen stehen lassen',
                'kategorie_id' => 2,
                'kategorie' => 'category.container',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTAS00002',
                'nutzer' => $user1,
                'name' => '(TEST)Selbstbau Rechner i7 mit Nvidia GTX 970 SLI',
                'verwendung' => '(TEST)Eine remote Bitcoinsoftware wurde installiert und auf ein unbekannte Konto gemint',
                'kategorie_id' => 0,
                'kategorie' => 'category.exhibit',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTAS00003',
                'nutzer' => $user3,
                'name' => '(TEST)NAS Server QNAP Server',
                'verwendung' => '(TEST)Es wurden Spuren von KiPo Material gefunden',
                'kategorie_id' => 0,
                'kategorie' => 'category.exhibit',
                'status_id' => 0,
            ],
            [
                'barcode_id' => 'DTHD00020',
                'nutzer' => $user1,
                'name' => '(TEST)WD 256 GB 3.5 Zoll externe Intern',
                'verwendung' => '(TEST)Gefunden aus einem älteren Rechner',
                'kategorie_id' => 3,
                'kategorie' => 'category.hdd',
                'status_id' => 0,
                'hdd' => [
                    'bauart' => 'intern',
                    'formfaktor' => '3,5',
                    'groesse' => 2000,
                    'groessealt' => 2000,
                    'modell' => 'WD4AU0078',
                    'hersteller' => 'WD',
                    'sn' => '6777886546',
                    'pn' => 'KlllU',
                    'anschluss' => 'SATA',
                ],
            ],
            [
                'barcode_id' => 'DTHD00024',
                'nutzer' => $user1,
                'name' => '(TEST)Hitachi Ultrastar 1TB',
                'verwendung' => '',
                'kategorie_id' => 3,
                'kategorie' => 'category.hdd',
                'status_id' => 0,
                'hdd' => [
                    'bauart' => 'intern',
                    'formfaktor' => '3,5',
                    'groesse' => 1000,
                    'groessealt' => 1000,
                    'modell' => 'K900',
                    'hersteller' => 'Hitachi',
                    'sn' => '7765398176',
                    'pn' => 'ABCDFG',
                    'anschluss' => 'SATA',
                ],
            ],
            [
                'barcode_id' => 'DTHD00025',
                'nutzer' => $user1,
                'name' => '(TEST)Hitachi Ultrastar 2TB',
                'verwendung' => '',
                'kategorie_id' => 3,
                'kategorie' => 'category.hdd',
                'status_id' => 0,
                'hdd' => [
                    'bauart' => 'intern',
                    'formfaktor' => '3,5',
                    'groesse' => 2000,
                    'groessealt' => 2000,
                    'modell' => 'K900',
                    'hersteller' => 'Hitachi',
                    'sn' => '3234512322',
                    'pn' => 'GFEDCA',
                    'anschluss' => 'SATA',
                ],
            ],
            [
                'barcode_id' => 'DTAS00004',
                'nutzer' => $user1,
                'name' => '(TEST)Intel SSD 430 256GB',
                'verwendung' => '',
                'kategorie_id' => 5,
                'kategorie' => 'category.exhibit.hdd',
                'status_id' => 0,
                'hdd' => [
                    'bauart' => 'intern',
                    'formfaktor' => '2,5',
                    'groesse' => 256,
                    'groessealt' => 256,
                    'modell' => '430',
                    'hersteller' => 'Intel',
                    'sn' => '3344556677',
                    'pn' => 'JUHGFDGHK',
                    'anschluss' => 'SATA',
                ],
            ],
            [
                'barcode_id' => 'DTAS00005',
                'nutzer' => $user1,
                'name' => '(TEST)Hitachi 20 GB hdd',
                'verwendung' => 'Befinden sich verschüsselte Daten',
                'kategorie_id' => 5,
                'kategorie' => 'category.exhibit.hdd',
                'status_id' => 0,
                'hdd' => [
                    'bauart' => 'intern',
                    'formfaktor' => '3,5',
                    'groesse' => 20,
                    'groessealt' => 20,
                    'modell' => 'oldware',
                    'hersteller' => 'Hitachi',
                    'sn' => '123321123',
                    'pn' => 'UJHTNMLOI',
                    'anschluss' => 'ATA',
                ],
            ],
            [
                'barcode_id' => 'DTHW00006',
                'nutzer' => $user3,
                'name' => '(TEST)Werkzeugregal',
                'verwendung' => '(TEST)Von einem Schwedischen Versandhandel besorgt',
                'kategorie_id' => 2,
                'kategorie' => 'category.container',
                'status_id' => 0,
            ],
        ];

        // first add Objekt
        foreach ($config as $id => $entry) {
            // add objekt
            $obj = new Objekt();
            $obj->setBarcode($entry['barcode_id']);
            $obj->setNutzer($entry['nutzer']);
            $obj->setName($entry['name']);
            $obj->setVerwendung($entry['verwendung']);
            $obj->setKategorie($entry['kategorie_id']);
            $obj->setStatus($entry['status_id']);
            $manager->persist($obj);
            $this->addReference(self::OBJEKT_REFERENCE.$id, $obj);
        }

        // save all to db
        $manager->flush();

        // second add datentraeger
        foreach ($config as $id => $entry) {
            // add datentraeger
            if (!key_exists('hdd', $entry)) {
                continue;
            }
            $entry['hdd']['barcode_id'] = $entry['barcode_id'];
            $hdd = new Datentraeger($entry['hdd']);
            $manager->persist($hdd);
            $this->addReference(self::DATENTRAEGER_REFERENCE.$id, $hdd);
        }
        // save all to db
        $manager->flush();

        // add standort relations
        $hw2 = $manager->find(Objekt::class, 'DTHW00002');
        $hw4 = $manager->find(Objekt::class, 'DTHW00004');
        $hw6 = $manager->find(Objekt::class, 'DTHW00006');
        $hw2->setStandort($hw6);
        $hw2->setStatus(7);
        $hw4->setStandort($hw2);
        $hw4->setStatus(7);

        $manager->persist($hw2);
        $manager->persist($hw4);

        // save all to db
        $manager->flush();

        // Historie objects
        $his_hw2 = new HistorieObjekt($hw2);
        $his_hw2->setVerwendung('(TEST)Wird zum Lagern von Asservaten gebraucht');
        $his_hw2->setSystemaktion(0);
        $his_hw2->setStatusId(0);
        $his_hw2->setNutzerId($hw2->getNutzer());
        $his_hw2->setZeitstempelumsetzung($his_hw2->getZeitstempel());
        $his_hw4 = new HistorieObjekt($hw4);
        $his_hw4->setVerwendung('(TEST)Für Mobilen Einsatz');
        $his_hw4->setSystemaktion(0);
        $his_hw4->setStatusId(0);
        $his_hw4->setNutzerId($hw4->getNutzer());
        $his_hw4->setZeitstempelumsetzung($his_hw4->getZeitstempel());

        $manager->persist($his_hw2);
        $manager->persist($his_hw4);

        // save all to db
        $manager->flush();
    }
}
