<?php

namespace App\DataFixtures;

use App\Entity\Asset;
use App\Entity\AssetHistory;
use App\Entity\Drive;
use App\Entity\Nutzer;
use App\Enum\AssetCategory;
use App\Enum\AssetState;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Loads objekts and datentraeger for dev purposes.
 *
 * @author Ben Brookserial_numberieder
 */
class AssetFixtures extends Fixture implements DependentFixtureInterface
{
    public const ASSET_REFERENCE = 'dt-asset-';
    public const DRIVE_REFERENCE = 'dt-drive-';
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
                'barcode' => 'DTAS00001',
                'nutzer' => $user1,
                'name' => '(TEST)Hitachi Festplatte',
                'usage' => '(TEST)Sind gelöschte Beweise drauf',
                'category' => AssetCategory::Exhibit,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTHW00007',
                'nutzer' => $user1,
                'name' => '(TEST)Thinkpad e330',
                'usage' => '(TEST)Ediscovery',
                'category' => AssetCategory::Equipment,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTHD00021',
                'nutzer' => $user1,
                'name' => '(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte',
                'usage' => '(TEST)Wird für Ein Asservat benötigt',
                'category' => AssetCategory::Hdd,
                'state' => AssetState::Added,
                'hdd' => [
                    'type' => '',
                    'formFactor' => '',
                    'size' => 0,
                    'sizealt' => 0,
                    'model' => '',
                    'manufacturer' => '',
                    'serial_number' => '',
                    'product_number' => '',
                    'connector' => '',
                ],
            ],
            [
                'barcode' => 'DTHW00001',
                'nutzer' => $user1,
                'name' => '(TEST)Encase Koffer mit speziffischen Inhalt',
                'usage' => '',
                'category' => AssetCategory::Equipment,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTHD00022',
                'nutzer' => $user1,
                'name' => '(TEST)Toshiba 500GB',
                'usage' => '(TEST)Austauschplatte Für den Server',
                'category' => AssetCategory::Hdd,
                'state' => AssetState::Added,
                'hdd' => [
                    'type' => 'intern',
                    'formFactor' => '3,5',
                    'size' => 500,
                    'sizealt' => 500,
                    'model' => 'model 1',
                    'manufacturer' => 'Toshiba',
                    'serial_number' => '89437809756B',
                    'product_number' => 'GHII9',
                    'connector' => 'SATA',
                ],
            ],
            [
                'barcode' => 'DTHD00023',
                'nutzer' => $user1,
                'name' => '(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte',
                'usage' => '(TEST)Notfallplatte für Forensikkoffer',
                'category' => AssetCategory::Hdd,
                'state' => AssetState::Added,
                'hdd' => [
                    'type' => 'extern',
                    'formFactor' => '2,5',
                    'size' => 2000,
                    'sizealt' => 2000,
                    'model' => 'T00JH9II',
                    'manufacturer' => 'Toshiba',
                    'serial_number' => '89437809756C',
                    'product_number' => 'GHII9',
                    'connector' => 'USB',
                ],
            ],
            [
                'barcode' => 'DTHW00002',
                'nutzer' => $user1,
                'name' => '(TEST)Schrank',
                'usage' => '(TEST)Wird zum Lagern von Asservaten gebraucht',
                'category' => AssetCategory::Container,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTHW00003',
                'nutzer' => $user3,
                'name' => '(TEST)Papierbox',
                'usage' => '(TEST)Wird zum Lagern von HDDs gebraucht',
                'category' => AssetCategory::Container,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTHW00004',
                'nutzer' => $user1,
                'name' => '(TEST)Peli Case',
                'usage' => '(TEST)Für Mobilen Einsatz',
                'category' => AssetCategory::Container,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTHW00005',
                'nutzer' => $user3,
                'name' => '(TEST)Pappkarton',
                'usage' => '(TEST)Zwecks Dringlichkeit in das System eingetragen, nicht im Regen stehen lassen',
                'category' => AssetCategory::Container,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTAS00002',
                'nutzer' => $user1,
                'name' => '(TEST)Selbstbau Rechner i7 mit Nvidia GTX 970 SLI',
                'usage' => '(TEST)Eine remote Bitcoinsoftware wurde installiert und auf ein unbekannte Konto gemint',
                'category' => AssetCategory::Exhibit,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTAS00003',
                'nutzer' => $user3,
                'name' => '(TEST)NAS Server QNAP Server',
                'usage' => '(TEST)Es wurden Spuren von KiPo Material gefunden',
                'category' => AssetCategory::Exhibit,
                'state' => AssetState::Added,
            ],
            [
                'barcode' => 'DTHD00020',
                'nutzer' => $user1,
                'name' => '(TEST)WD 256 GB 3.5 Zoll externe Intern',
                'usage' => '(TEST)Gefunden aus einem älteren Rechner',
                'category' => AssetCategory::Hdd,
                'state' => AssetState::Added,
                'hdd' => [
                    'type' => 'intern',
                    'formFactor' => '3,5',
                    'size' => 2000,
                    'sizealt' => 2000,
                    'model' => 'WD4AU0078',
                    'manufacturer' => 'WD',
                    'serial_number' => '6777886546',
                    'product_number' => 'KlllU',
                    'connector' => 'SATA',
                ],
            ],
            [
                'barcode' => 'DTHD00024',
                'nutzer' => $user1,
                'name' => '(TEST)Hitachi Ultrastar 1TB',
                'usage' => '',
                'category' => AssetCategory::Hdd,
                'state' => AssetState::Added,
                'hdd' => [
                    'type' => 'intern',
                    'formFactor' => '3,5',
                    'size' => 1000,
                    'sizealt' => 1000,
                    'model' => 'K900',
                    'manufacturer' => 'Hitachi',
                    'serial_number' => '7765398176',
                    'product_number' => 'ABCDFG',
                    'connector' => 'SATA',
                ],
            ],
            [
                'barcode' => 'DTHD00025',
                'nutzer' => $user1,
                'name' => '(TEST)Hitachi Ultrastar 2TB',
                'usage' => '',
                'category' => AssetCategory::Hdd,
                'state' => AssetState::Added,
                'hdd' => [
                    'type' => 'intern',
                    'formFactor' => '3,5',
                    'size' => 2000,
                    'sizealt' => 2000,
                    'model' => 'K900',
                    'manufacturer' => 'Hitachi',
                    'serial_number' => '3234512322',
                    'product_number' => 'GFEDCA',
                    'connector' => 'SATA',
                ],
            ],
            [
                'barcode' => 'DTAS00004',
                'nutzer' => $user1,
                'name' => '(TEST)Intel SSD 430 256GB',
                'usage' => '',
                'category' => AssetCategory::ExhibitHdd,
                'state' => AssetState::Added,
                'hdd' => [
                    'type' => 'intern',
                    'formFactor' => '2,5',
                    'size' => 256,
                    'sizealt' => 256,
                    'model' => '430',
                    'manufacturer' => 'Intel',
                    'serial_number' => '3344556677',
                    'product_number' => 'JUHGFDGHK',
                    'connector' => 'SATA',
                ],
            ],
            [
                'barcode' => 'DTAS00005',
                'nutzer' => $user1,
                'name' => '(TEST)Hitachi 20 GB hdd',
                'usage' => 'Befinden sich verschüsselte Daten',
                'category' => AssetCategory::ExhibitHdd,
                'state' => AssetState::Added,
                'hdd' => [
                    'type' => 'intern',
                    'formFactor' => '3,5',
                    'size' => 20,
                    'sizealt' => 20,
                    'model' => 'oldware',
                    'manufacturer' => 'Hitachi',
                    'serial_number' => '123321123',
                    'product_number' => 'UJHTNMLOI',
                    'connector' => 'ATA',
                ],
            ],
            [
                'barcode' => 'DTHW00006',
                'nutzer' => $user3,
                'name' => '(TEST)Werkzeugregal',
                'usage' => '(TEST)Von einem Schwedischen Versandhandel besorgt',
                'category' => AssetCategory::Container,
                'state' => AssetState::Added,
            ],
        ];

        // first add Asset
        foreach ($config as $id => $entry) {
            // add objekt
            $obj = new Asset();
            $obj->setBarcode($entry['barcode']);
            $obj->setModifiedBy($entry['nutzer']);
            $obj->setName($entry['name']);
            $obj->setUsage($entry['usage']);
            $obj->setCategory($entry['category']);
            $obj->setState($entry['state']);
            $obj->setLastUpdatedOn(new DateTime());
            $obj->setLastUpdatePerformedOn(new DateTime());
            $manager->persist($obj);
            $this->addReference(self::ASSET_REFERENCE.$id, $obj);
        }

        // save all to db
        $manager->flush();

        // second add datentraeger
        foreach ($config as $id => $entry) {
            $asset = $this->getReference(self::ASSET_REFERENCE.$id, Asset::class);
            // add datentraeger
            if (!key_exists('hdd', $entry)) {
                continue;
            }
            $entry['hdd']['barcode'] = $asset;
            $hdd = new Drive($entry['hdd']);
            $manager->persist($hdd);
            $this->addReference(self::DRIVE_REFERENCE.$id, $hdd);
        }
        // save all to db
        $manager->flush();

        // add location relations
        $hw2 = $manager->find(Asset::class, 'DTHW00002');
        $hw4 = $manager->find(Asset::class, 'DTHW00004');
        $hw6 = $manager->find(Asset::class, 'DTHW00006');
        $hw2->setLocation($hw6);
        $hw2->setState(AssetState::StoredInContainer);
        $hw4->setLocation($hw2);
        $hw4->setState(AssetState::StoredInContainer);

        $manager->persist($hw2);
        $manager->persist($hw4);

        // save all to db
        $manager->flush();

        // Historie objects
        $his_hw2 = AssetHistory::fromAsset($hw2);
        $his_hw2->setusage('(TEST)Wird zum Lagern von Asservaten gebraucht');
        $his_hw2->setSystemAction(0);
        $his_hw2->setState(AssetState::Added);
        $his_hw2->setModifiedBy($hw2->getModifiedBy());
        $his_hw2->setLastUpdatePerformedOn($hw2->getLastUpdatedOn());
        $his_hw4 = AssetHistory::fromAsset($hw4);
        $his_hw4->setusage('(TEST)Für Mobilen Einsatz');
        $his_hw4->setSystemAction(0);
        $his_hw4->setState(AssetState::Added);
        $his_hw4->setModifiedBy($hw4->getModifiedBy());
        $his_hw4->setLastUpdatePerformedOn($hw4->getLastUpdatedOn());

        $manager->persist($his_hw2);
        $manager->persist($his_hw4);

        // save all to db
        $manager->flush();
    }
}
