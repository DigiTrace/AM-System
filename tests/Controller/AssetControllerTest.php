<?php

namespace App\Tests\Controller;

use App\Entity\AssetHistory;
use App\Entity\Objekt;
use App\Repository\AssetHistoryRepository;
use App\Repository\AssetRepository;
use App\Repository\DriveRepository;
use App\Repository\ObjektRepository;
use App\Tests\_support\BaseWebTestCase;
use App\Tests\Factory\AssetFactory;
use App\Tests\Factory\FallFactory;
use App\Tests\Factory\ObjektFactory;
use Symfony\Component\DomCrawler\Crawler;
use App\Tests\_support\TestHelper;

use App\Enum\AssetCategory as Category;
use App\Enum\AssetState as State;

/**
 * @author Ben Brooksnieder
 */
class AssetControllerTest extends BaseWebTestCase
{
    public function addValidProvider() {
        yield 'SimpleExhibit' => [[
            'barcode' => 'DTAS00001',
            'name' => '(TEST)Hitachi Festplatte',
            'usage' => '(TEST)Sind gelöschte Beweise drauf',
            'category' => Category::Exhibit->value,
        ]];
        yield 'StorageExhibit' => [[
            'barcode' => 'DTAS10001',
            'name' => 'Behälter Hitachi Festplatte',
            'usage' => 'Kann Dinge beinhalten',
            'category' => Category::Exhibit->value,
            'storageOverride' => 1,
        ]];
        yield 'SimpleEquipment' => [[
            'barcode' => 'DTHW00007',
            'name' => '(TEST)Thinkpad e330',
            'usage' => '(TEST)Edriveovery',
            'category' => Category::Equipment->value,
        ]];
        yield 'EmptyUsage' => [[
            'barcode' => 'DTHW00001',
            'name' => '(TEST)Encase Koffer mit speziffischen Inhalt',
            'usage' => null,
            'category' => Category::Equipment->value,
        ]];
        yield 'SimpleContainer' => [[
            'barcode' => 'DTHW00002',
            'name' => '(TEST)Schrank',
            'usage' => '(TEST)Wird zum Lagern von Asservaten gebraucht',
            'category' => Category::Container->value,
        ]];
        yield 'NoStorageContainer' => [[
            'barcode' => 'DTHW00033',
            'name' => 'Void Schrank',
            'usage' => 'kann nichts beinhalten',
            'category' => Category::Container->value,
            'storageOverride' => 2,
        ]];
        yield 'SimpleHDD' => [[
            'barcode' => 'DTHD00022',
            'name' => '(TEST)Toshiba 423GB',
            'usage' => '(TEST)Austauschplatte Für den Server',
            'category' => Category::Hdd->value,
        ], [
            'formFactor' => '3,5',
            'type' => 'intern',
            'size' => '423',
            'size_choice' => '16',
            'model' => 'Modell 1',
            'manufacturer' => 'Toshiba',
            'serialNumber' => '1231412341231',
            'productNumber' => 'GHII9',
            'connector' => 'SATA',
        ]];
        yield 'SimpleHDDSizeChoice' => [[
            'barcode' => 'DTHD00023',
            'name' => '(TEST)Toshiba 250GB',
            'usage' => 'Test',
            'category' => Category::Hdd->value,
        ], [
            'formFactor' => '3,5',
            'type' => 'intern',
            'size' => null,
            'size_choice' => '250',
            'model' => 'Modell 2',
            'manufacturer' => 'Toshiba',
            'serialNumber' => '1233221',
            'productNumber' => 'GHII9',
            'connector' => 'SATA',
        ]];
        yield 'EmptyHDD' => [[
            'barcode' => 'DTHD00001',
            'name' => '(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte',
            'usage' => '(TEST)Wird für Ein Asservat benötigt',
            'category' => Category::Hdd->value,
        ]];
        yield 'SimpleRecord' => [[
            'barcode' => 'DTAK10203',
            'name' => 'Schallplatte',
            'usage' => 'Zum Musikhören',
            'category' => Category::Record->value,
        ]];
        yield 'SimpleExhibitHdd' => [[
            'barcode' => 'DTAS00004',
            'name' => '(TEST)Intel SSD 430 256GB',
            'usage' => null,
            'category' => Category::ExhibitHdd->value,
        ], [
            'formFactor' => '2,5',
            'type' => 'intern',
            'size' => '256',
            'size_choice' => '250',
            'model' => '430',
            'manufacturer' => 'Intel',
            'serialNumber' => '3344556677',
            'productNumber' => 'JUHGFDGHK',
            'connector' => 'USB',
        ]];
    }

    /**
     * @dataProvider addValidProvider
     */
    public function testAddValid($asset, $drive = null) {
        // setup
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = "add_asset";
        $form = $crawler->selectButton($name . "[save]")->form();

        // add asset parameters
        foreach ($asset as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        // add datentraeger paramters
        foreach ($drive ?? [] as $key => $value) {
            $form["{$name}[drive][{$key}]"] = $value ?? '';
        }


        $client->submit($form);
        $this->assertResponseRedirects("/objekt/{$asset['barcode']}");

        // default values
        $asset['usage'] ??= null;
        $asset['note'] ??= null;
        if (isset($asset['storageOverride'])) {
            $asset['storageOverride'] = $asset['storageOverride'] == 1;
        } else {
            $asset['storageOverride'] = null;
        }
        
        if($drive) {
            $drive['formFactor'] ??= null;
            $drive['type'] ??= null;
            $drive['size'] ??= null;
            $drive['size_choice'] ??= null;
            $drive['model'] ??= null;
            $drive['manufacturer'] ??= null;
            $drive['serialNumber'] ??= null;
            $drive['productNumber'] ??= null;
            $drive['connector'] ??= null;
        }
        
        // also these values should be set
        $asset['state'] = State::Added->value;
        $asset['systemAction'] = false;
        $asset['modifiedBy'] = $this->getUser('user');
        $asset['reservedBy'] = null;
        $asset['location'] = null;
        $asset['case'] = null;

        // check database for correct entry
        $this->seeInDatabase(AssetRepository::class, $asset);
        if (null !== $drive) {
            $drive['barcode'] = $asset['barcode'];
            if ($drive['size_choice']) {
                $drive['size'] ??= $drive['size_choice'];
                unset($drive['size_choice']);
            }
            $this->seeInDatabase(DriveRepository::class, $drive);
        }

        return $client;
    }

    /**
     * @dataProvider addValidProvider
     * @depends testAddValid
     */
    public function testAddValidWithCase($asset, $drive = null) {
        $caseFactory = FallFactory::new();
        $case = $caseFactory->create();
        
        // setup
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = "add_asset";
        $form = $crawler->selectButton($name . "[save]")->form();

        // search for case and submit
        $form["{$name}[case_search]"] = $case->getBeschreibung();
        $crawler = $client->submit($form);

        $form = $crawler->selectButton($name . "[save]")->form();
        // add asset parameters
        foreach ($asset as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        // add datentraeger paramters
        foreach ($drive ?? [] as $key => $value) {
            $form["{$name}[drive][{$key}]"] = $value ?? '';
        }

        // add case id
        $form["{$name}[case_search]"] = $case->getBeschreibung();
        $form["{$name}[case]"] = $case->getId();
        $crawler = $client->submit($form);
        $this->assertResponseRedirects("/objekt/{$asset['barcode']}");

        // default values
        $asset['usage'] ??= null;
        $asset['note'] ??= null;
        if (isset($asset['storageOverride'])) {
            $asset['storageOverride'] = $asset['storageOverride'] == 1;
        } else {
            $asset['storageOverride'] = null;
        }

        if($drive) {
            $drive['formFactor'] ??= null;
            $drive['type'] ??= null;
            $drive['size'] ??= null;
            $drive['size_choice'] ??= null;
            $drive['model'] ??= null;
            $drive['manufacturer'] ??= null;
            $drive['serialNumber'] ??= null;
            $drive['productNumber'] ??= null;
            $drive['connector'] ??= null;
        }

        // also these values should be set
        $asset['state'] = State::Added->value;
        $asset['systemAction'] = false;
        $asset['modifiedBy'] = $this->getUser('user');
        $asset['reservedBy'] = null;
        $asset['location'] = null;
        $asset['case'] = null;

        $history = $asset;
        $history['asset'] = $history['barcode'];
        unset($history['barcode']);
        unset($history['name']);
        unset($history['note']);
        unset($history['category']);
        unset($history['storageOverride']);
        
        // now check for system changes
        $asset['state'] = State::AddedToCase->value;
        $asset['case'] = $case->_real();
        unset($asset['usage']);
        
        // check database for correct entry
        $this->seeInDatabase(AssetRepository::class, $asset);
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        
        if (null !== $drive) {
            $drive['barcode'] = $asset['barcode'];
            if ($drive['size_choice']) {
                $drive['size'] ??= $drive['size_choice'];
                unset($drive['size_choice']);
            }
            $this->seeInDatabase(DriveRepository::class, $drive);
        }

        return $client;
    }

    /** 
     * @depends testAddValidWithCase
     */
    public function testAddInvalidMissingData() {
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = "add_asset";
        $form = $crawler->selectButton($name . "[save]")->form();

        $asset = [];

        // add asset parameters
        foreach ($asset as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        // add datentraeger paramters
        foreach ($drive ?? [] as $key => $value) {
            $form["{$name}[drive][{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
    }

    /** 
     * @depends testAddInvalidMissingData
     */
    public function testAddInvalidBarcode() {
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = "add_asset";
        $form = $crawler->selectButton($name . "[save]")->form();

        $asset = [
            'barcode' => 'asdad',
            'name' => 'Test',
            'usage' => 'Test',
            'category' => Category::Exhibit->value,
        ];

        // add asset parameters
        foreach ($asset as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        // add datentraeger paramters
        foreach ($drive ?? [] as $key => $value) {
            $form["{$name}[drive][{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
        $this->dontSeeInDatabase(AssetRepository::class, $asset);
    }

    /** 
     * @depends testAddInvalidBarcode
     */
    public function testAddInvalidWrongCategory() {
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = "add_asset";
        $form = $crawler->selectButton($name . "[save]")->form();

        $asset = [
            'barcode' => 'DTHD12345',
            'name' => 'Test',
            'usage' => 'Test',
            'category' => Category::Exhibit->value,
        ];

        // add asset parameters
        foreach ($asset as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        // add datentraeger paramters
        foreach ($drive ?? [] as $key => $value) {
            $form["{$name}[drive][{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
        $this->dontSeeInDatabase(AssetRepository::class, $asset);
    }

    /** 
     * @depends testAddInvalidWrongCategory
     */
    public function testAddInvalidDuplicate() {
        $factory = AssetFactory::new();
        $duplicate = $factory->create();
        
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = "add_asset";
        $form = $crawler->selectButton($name . "[save]")->form();

        $asset = [
            'barcode' => $duplicate->getBarcode(),
            'name' => 'Test',
            'usage' => 'Test',
            'category' => Category::Exhibit->value,
        ];

        // add asset parameters
        foreach ($asset as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        // add datentraeger paramters
        foreach ($drive ?? [] as $key => $value) {
            $form["{$name}[drive][{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
        $this->dontSeeInDatabase(AssetRepository::class, $asset);
    }

    public function testEditActionValid() {
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "edit_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/editieren");
        $form = $crawler->selectButton($name . "[save]")->form();
        $data = [
            'name' => 'Fritz',
            'usage' => 'Geändert',
            'note' => 'Hä',
        ];

        $drive = $asset->getDrive();
        $driveData = [
            'formFactor' => $drive?->getFormFactor() . "_test",
            'type' => $drive?->getType() . "_test",
            'size' => $drive?->getSize() . "0",
            'manufacturer' => $drive?->getManufacturer() . "_test",
            'model' => $drive?->getModel() . "_test",
            'serialNumber' => $drive?->getSerialNumber() . "_test",
            'productNumber' => $drive?->getProductNumber() . "_test",
            'connector' => $drive?->getConnector() . "_test",
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        // drive data to form
        foreach ($driveData as $key => $value) {
            $form["{$name}[drive][{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/$barcode");
        $data['barcode'] = $barcode;
        $data['state'] = State::Edited;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        // check changes
        $this->seeInDatabase(AssetRepository::class, $data);
        // check drive for changes
        $driveData['barcode'] = $asset;
        $this->seeInDatabase(DriveRepository::class, $driveData);
    }

    /**
     * @depends testEditActionValid
     */
    public function testEditActionInvalidNoChanges() {
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "edit_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/editieren");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'barcode' => $barcode,
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
        ];

        // no changes
        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert', 'asset.action.edit.no_changes_made');

        // check history
        $this->dontSeeInDatabase(AssetHistoryRepository::class, $history);
        // check changes
        $this->seeInDatabase(AssetRepository::class, $data);
        $data['state'] = State::Edited;
        $this->dontSeeInDatabase(AssetRepository::class, $data);
        
    }

    public function testNullActionValid() {
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "action_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/nullen");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => 'Genullt',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Cleaned;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    /**
     * @depends testNullActionValid
     */
    public function testNullActionInvalidCategory() {
        $factory = AssetFactory::new();
        $asset = $factory->record()->create();
        $barcode = $asset->getBarcode();
        
        $client = static::createClient();
        $this->loginUser($client)->request('GET', "/objekt/$barcode/nullen");
        $this->assertResponseRedirects("/objekt/$barcode");
    }

    /**
     * @depends testNullActionInvalidCategory
     */
    public function testNullActionInvalidSameState() {
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->with(['state' => State::Cleaned])->create();
        $barcode = $asset->getBarcode();
        
        $client = static::createClient();
        $this->loginUser($client)->request('GET', "/objekt/$barcode/nullen");
        $this->assertResponseRedirects("/objekt/$barcode");
    }

    /**
     * @depends testNullActionInvalidSameState
     */
    public function testNullActionInvalidState() {
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->lost()->create();
        $barcode = $asset->getBarcode();
        
        $client = static::createClient();
        $this->loginUser($client)->request('GET', "/objekt/$barcode/nullen");
        $this->assertResponseRedirects("/objekt/$barcode");
    }


    public function testUseActionValid() {
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "action_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/verwenden");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => 'benutzt',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Used;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    /**
     * @depends testUseActionValid
     */
    public function testUseActionInvalidUsage() {
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "action_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/verwenden");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => '',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');

        // check history
        $this->dontSeeInDatabase(AssetHistoryRepository::class, $history);
        $history['barcode'] = $barcode;
        unset($history['asset']);
        $this->seeInDatabase(AssetRepository::class, $history);
    }

    public function testDestroyActionValid() {
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "action_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/vernichtet");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => 'Wech',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Destroyed;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    /**
     * @depends testDestroyActionValid
     */
    public function testDestroyActionInvalidSameState() {
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->with(['state' => State::Destroyed])->create();
        $barcode = $asset->getBarcode();
        
        $client = static::createClient();
        $this->loginUser($client)->request('GET', "/objekt/$barcode/vernichtet");
        $this->assertResponseRedirects("/objekt/$barcode");
    }

    public function testLostActionValid() {
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "action_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/verloren");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => 'Wech',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Lost;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }    

    /**
     * @depends testLostActionValid
     */
    public function testLostActionInvalidSameState() {
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->with(['state' => State::Lost])->create();
        $barcode = $asset->getBarcode();
        
        $client = static::createClient();
        $this->loginUser($client)->request('GET', "/objekt/$barcode/verloren");
        $this->assertResponseRedirects("/objekt/$barcode");
    }


    public function testHandoverActionValid() {
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "action_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/uebergeben");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => 'wech gegeben',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::HandoverPerson;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    /**
     * @depends testHandoverActionValid
     */
    public function testHandoverActionInvalidUsage() {
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];
        
        $name = "action_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/uebergeben");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => '',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');

        // check history
        $this->dontSeeInDatabase(AssetHistoryRepository::class, $history);
        $history['barcode'] = $barcode;
        unset($history['asset']);
        $this->seeInDatabase(AssetRepository::class, $history);
    }


    public function testReserveActionValid() {
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'reservedBy' => $asset->getReservedBy(),
        ];
        
        $name = "action_asset";
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/reservieren");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => 'wech gegeben',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Reserved;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');
        $data['reservedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    /**
     * @depends testReserveActionValid
     */
    public function testReserveActionInvalidSameReservedBy() {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->reservedBy($this->getUser('user'))->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'reservedBy' => $asset->getReservedBy(),
        ];
        
        $name = "action_asset";
        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/reservieren");
        $form = $crawler->selectButton($name . "[save]")->form();

        $data = [
            'usage' => 'wech gegeben',
        ];

        // data to form
        foreach ($data as $key => $value) {
            $form["{$name}[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        // check history
        $this->dontSeeInDatabase(AssetHistoryRepository::class, $history);
        $history['barcode'] = $barcode;
        unset($history['asset']);
        $this->seeInDatabase(AssetRepository::class, $history);
    }
}
