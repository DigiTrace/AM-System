<?php

namespace App\Tests\Controller;

use App\Enum\AssetCategory as Category;
use App\Enum\AssetState as State;
use App\Repository\AssetHistoryRepository;
use App\Repository\AssetRepository;
use App\Repository\DriveRepository;
use App\Tests\_support\BaseWebTestCase;
use App\Tests\Factory\AssetFactory;
use App\Tests\Factory\CaseFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * @author Ben Brooksnieder
 */
class AssetControllerTest extends BaseWebTestCase
{
    public static function notFoundUrlProvider()
    {
        yield 'Details' => [
            '/objekt/%s',
        ];
        yield 'Action' => [
            '/objekt/%s/editieren',
        ];
        yield 'SaveHddImageAction' => [
            '/objekt/%s/Asservatenimage/speichern/',
        ];
        yield 'UploadPicture' => [
            '/objekt/%s/upload',
        ];
    }

    #[DataProvider('notFoundUrlProvider')]
    public function testAssetNotFound($url)
    {
        $client = static::createClient();
        $barcode = 'DEADBEEF3';

        $crawler = $this->loginUser($client)->request('GET', sprintf($url, $barcode));

        $this->assertResponseRedirects('/objekte');
        $client->followRedirect();

        // alert symbol from validation errors
        $this->assertSelectorTextContains('.alert-danger', 'asset.error.not_found');
    }

    public static function addValidProvider()
    {
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

    #[DataProvider('addValidProvider')]
    public function testAddValid($asset, $drive = null)
    {
        // setup
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = 'add';
        $submit = 'save';
        $form = $crawler->selectButton("{$name}[{$submit}]")->form();

        // populate form
        $data = $asset;
        if ($drive) {
            $data['drive'] = $drive;
        }

        // submit form
        $form->setValues([$name => $data]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/{$asset['barcode']}");

        // default values
        $asset['usage'] ??= null;
        $asset['note'] ??= null;
        if (isset($asset['storageOverride'])) {
            $asset['storageOverride'] = 1 == $asset['storageOverride'];
        } else {
            $asset['storageOverride'] = null;
        }

        if ($drive) {
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
            $drive['asset'] = $asset['barcode'];
            if ($drive['size_choice']) {
                $drive['size'] ??= $drive['size_choice'];
                unset($drive['size_choice']);
            }
            $this->seeInDatabase(DriveRepository::class, $drive);
        }

        return $client;
    }

    #[Depends('testAddValid')]
    #[DataProvider('addValidProvider')]
    public function testAddValidWithCase($asset, $drive = null)
    {
        $client = static::createClient();
        $case = CaseFactory::createOne();

        // setup
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = 'add';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $data = $asset;
        if ($drive) {
            $data['drive'] = $drive;
        }

        // submit form
        $form->setValues([$name => $data]);
        $payload = $form->getPhpValues();
        $payload[$name]['case']['item'] = $case->getCaseId();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/{$asset['barcode']}");

        // default values
        $asset['usage'] ??= null;
        $asset['note'] ??= null;
        if (isset($asset['storageOverride'])) {
            $asset['storageOverride'] = 1 == $asset['storageOverride'];
        } else {
            $asset['storageOverride'] = null;
        }

        if ($drive) {
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
        $asset['state'] = State::AssignedCase->value;
        $asset['case'] = $case->_real();
        unset($asset['usage']);

        // check database for correct entry
        $this->seeInDatabase(AssetRepository::class, $asset);
        $this->seeInDatabase(AssetHistoryRepository::class, $history);

        if (null !== $drive) {
            $drive['asset'] = $asset['barcode'];
            if ($drive['size_choice']) {
                $drive['size'] ??= $drive['size_choice'];
                unset($drive['size_choice']);
            }
            $this->seeInDatabase(DriveRepository::class, $drive);
        }

        return $client;
    }

    #[Depends("testAddValidWithCase")]
    public function testAddInvalidMissingData()
    {
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // get form
        $name = 'add';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $data = [];

        // submit form
        $form->setValues([$name => $data]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
    }

    #[Depends('testAddInvalidMissingData')]
    public function testAddInvalidBarcode()
    {
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        $asset = [
            'barcode' => 'asdad',
            'name' => 'Test',
            'usage' => 'Test',
            'category' => Category::Exhibit->value,
        ];

        // get form
        $name = 'add';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $data = $asset;

        // submit form
        $form->setValues([$name => $data]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
        $this->dontSeeInDatabase(AssetRepository::class, $asset);
    }

    #[Depends('testAddInvalidBarcode')]
    public function testAddInvalidWrongCategory()
    {
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        $asset = [
            'barcode' => 'DTHD12345',
            'name' => 'Test',
            'usage' => 'Test',
            'category' => Category::Exhibit->value,
        ];

        // get form
        $name = 'add';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $data = $asset;

        // submit form
        $form->setValues([$name => $data]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
        $this->dontSeeInDatabase(AssetRepository::class, $asset);
    }

    #[Depends('testAddInvalidWrongCategory')]
    public function testAddInvalidDuplicate()
    {
        $client = static::createClient();

        $factory = AssetFactory::new();
        $duplicate = $factory->create();

        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        $asset = [
            'barcode' => $duplicate->getBarcode(),
            'name' => 'Test',
            'usage' => 'Test',
            'category' => Category::Exhibit->value,
        ];

        // get form
        $name = 'add';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $data = $asset;

        // submit form
        $form->setValues([$name => $data]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
        $this->dontSeeInDatabase(AssetRepository::class, $asset);
    }

    public function testActionAssetNotFound()
    {
        $client = static::createClient();
        $barcode = 'DTKP69996';
        $this->loginUser($client)->request('GET', "/objekt/$barcode/editieren");
        $this->assertResponseRedirects('/objekte');
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert', 'asset.error.not_found');
    }

    public function testEditActionValid()
    {
        $client = static::createClient();

        $factory = AssetFactory::new();
        $asset = $factory->hdd()->create();
        $asset->_disableAutoRefresh();
        $barcode = $asset->getBarcode();
        $drive = $asset->getDrive();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'name' => 'Fritz',
            'usage' => 'Geändert',
            'note' => 'Hä',
        ];

        $driveData = [
            'formFactor' => $drive?->getFormFactor().'_test',
            'type' => $drive?->getType().'_test',
            'size' => $drive?->getSize().'0',
            'manufacturer' => $drive?->getManufacturer().'_test',
            'model' => $drive?->getModel().'_test',
            'serialNumber' => $drive?->getSerialNumber().'_test',
            'productNumber' => $drive?->getProductNumber().'_test',
            'connector' => $drive?->getConnector().'_test',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/editieren");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;
        $formData['drive'] = $driveData;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
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
        $driveData['asset'] = $asset;
        $this->seeInDatabase(DriveRepository::class, $driveData);
    }

    #[Depends('testEditActionValid')]
    public function testEditActionInvalidNoChanges()
    {
        $client = static::createClient();
        $asset = AssetFactory::createOne();
        $asset->_disableAutoRefresh();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'barcode' => $barcode,
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
        ];
        $this->seeInDatabase(AssetRepository::class, $data);

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/editieren");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // no changes
        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert', 'asset.edit.no_changes_made');

        // check history
        $this->dontSeeInDatabase(AssetHistoryRepository::class, $history);
        // check changes
        $this->seeInDatabase(AssetRepository::class, $data);
        $data['state'] = State::Edited;
        $this->dontSeeInDatabase(AssetRepository::class, $data);
    }

    public function testNullActionValid()
    {
        $client = static::createClient();
        // TODO test for images
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'usage' => 'Genullt',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/nullen");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Cleaned;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testNullActionValid')]
    public function testNullActionInvalidCategory()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->record()->create();
        $barcode = $asset->getBarcode();

        $this->loginUser($client)->request('GET', "/objekt/$barcode/nullen");
        $this->assertResponseRedirects("/objekt/$barcode");
    }

    #[Depends('testNullActionInvalidCategory')]
    public function testNullActionInvalidSameState()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->with(['state' => State::Cleaned])->create();
        $barcode = $asset->getBarcode();

        $this->loginUser($client)->request('GET', "/objekt/$barcode/nullen");
        $this->assertResponseRedirects("/objekt/$barcode");
    }

    #[Depends('testNullActionInvalidSameState')]
    public function testNullActionInvalidState()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->lost()->create();
        $barcode = $asset->getBarcode();

        $this->loginUser($client)->request('GET', "/objekt/$barcode/nullen");
        $this->assertResponseRedirects("/objekt/$barcode");
    }

    public function testUseActionValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'usage' => 'benutzt',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/verwenden");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Used;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testUseActionValid')]
    public function testUseActionInvalidUsage()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'usage' => '',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/verwenden");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    public function testDestroyActionValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'usage' => 'Wech',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/vernichtet");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testDestroyActionValid')]
    public function testDestroyActionInvalidSameState()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->with(['state' => State::Destroyed])->create();
        $barcode = $asset->getBarcode();

        $this->loginUser($client)->request('GET', "/objekt/$barcode/vernichtet");
        $this->assertResponseRedirects("/objekt/$barcode");
    }

    public function testLostActionValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'usage' => 'Wech',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/verloren");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Lost;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testLostActionValid')]
    public function testLostActionInvalidSameState()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->hdd()->with(['state' => State::Lost])->create();
        $barcode = $asset->getBarcode();

        $this->loginUser($client)->request('GET', "/objekt/$barcode/verloren");
        $this->assertResponseRedirects("/objekt/$barcode");
    }

    public function testHandoverActionValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'usage' => 'wech gegeben',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/uebergeben");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::HandoverPerson;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testHandoverActionValid')]
    public function testHandoverActionInvalidUsage()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
        ];

        $data = [
            'usage' => '',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/uebergeben");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    public function testReserveActionValid()
    {
        $client = static::createClient();
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

        $data = [
            'usage' => 'wech gegeben',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/reservieren");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
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

    #[Depends('testReserveActionValid')]
    public function testReserveActionInvalidSameReservedBy()
    {
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

        $this->loginUser($client)->request('GET', "/objekt/$barcode/reservieren");
        $this->assertResponseRedirects("/objekt/$barcode");

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    public function testUnreserveActionValid()
    {
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

        $data = [
            'usage' => 'unreserved',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/reservierung/aufheben");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::UnbindReservation;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');
        $data['reservedBy'] = null;

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testUnreserveActionValid')]
    public function testUnreserveActionInvaliNotReserved()
    {
        $client = static::createClient();
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

        $this->loginUser($client)->request('GET', "/objekt/$barcode/reservierung/aufheben");
        $this->assertResponseRedirects("/objekt/$barcode");

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }
    // TODO case where user tries to unbind reservation from different user

    public function testPullOutOfContainerActionValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $container = $factory->container()->create();
        $asset = $factory->storedIn($container->_real())->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
        ];

        $data = [
            'usage' => 'unstore',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/entnehmen");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::PulledOutOfContainer;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');
        $data['location'] = null;

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testPullOutOfContainerActionValid')]
    public function testPullOutOfContainerActionInvalidNotStored()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
        ];

        $this->loginUser($client)->request('GET', "/objekt/$barcode/entnehmen");
        $this->assertResponseRedirects("/objekt/$barcode");

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    public function testRemoveFromCaseActionValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $case = CaseFactory::createOne();
        $asset = $factory->assignedToCase($case->_real())->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'case' => $asset->getCase(),
        ];

        $data = [
            'usage' => 'not assigned anymore',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/aus/Fall/entfernen");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::RemovedFromCase;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');
        $data['case'] = null;

        // check history
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testRemoveFromCaseActionValid')]
    public function testRemoveFromCaseActionInvalidNotAssigned()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'case' => $asset->getCase(),
        ];

        $this->loginUser($client)->request('GET', "/objekt/$barcode/aus/Fall/entfernen");
        $this->assertResponseRedirects("/objekt/$barcode");

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    public function testNeutralizeActionValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $case = CaseFactory::createOne();
        $container = $factory->container()->create();

        $asset = $factory
            ->hdd()
            ->assignedToCase($case->_real())
            ->storedIn($container->_real())
            ->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
            'case' => $asset->getCase(),
        ];

        $data = [
            'usage' => 'not assigned anymore',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/neutralisieren");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::Cleaned;
        $data['systemAction'] = true;
        $data['modifiedBy'] = $this->getUser('user');
        $data['location'] = null;
        $data['case'] = null;

        // check history for state change
        $this->seeInDatabase(AssetHistoryRepository::class, $history);

        // // check history for state change
        // $history['state'] = $data['state'];
        // $history['systemAction'] = $data['systemAction'];
        // $history['modifiedBy'] = $data['modifiedBy'];
        // $history['usage'] = $data['usage'];
        // $this->seeInDatabase(AssetHistoryRepository::class, $history);

        // // check history for location change
        // $history['location'] = $data['location'];
        // $this->seeInDatabase(AssetHistoryRepository::class, $history);

        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testNeutralizeActionValid')]
    public function testNeutralizeActionInvalidNotHdd()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $asset = $factory->exhibit()->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
            'case' => $asset->getCase(),
        ];

        $this->loginUser($client)->request('GET', "/objekt/$barcode/neutralisieren");
        $this->assertResponseRedirects("/objekt/$barcode");

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    #[Depends('testNeutralizeActionValid')]
    public function testNeutralizeActionInvalidNotApplicable()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $asset = $factory->hdd()->with(['state' => State::Cleaned])->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
            'case' => $asset->getCase(),
        ];

        $this->loginUser($client)->request('GET', "/objekt/$barcode/neutralisieren");
        $this->assertResponseRedirects("/objekt/$barcode");

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    public function testStoreActionValidContainer()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $container = $factory->container()->create();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
        ];

        $data = [
            'usage' => 'test',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/einlegen/in/");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();
        $payload[$name]['location']['item'] = $container->getBarcode();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::StoredInContainer;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');
        $data['location'] = $container->_real();

        // assert correct database changes
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    public function testStoreActionValidStorageOverride()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $container = $factory
            ->record()
            ->with(['storageOverride' => true])
            ->create();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
        ];

        $data = [
            'usage' => 'test',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/einlegen/in/");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();
        $payload[$name]['location']['item'] = $container->getBarcode();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::StoredInContainer;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');
        $data['location'] = $container->_real();

        // assert correct database changes
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testStoreActionValidContainer')]
    public function testStoreActionInvalidNotContainer()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $container = $factory
            ->record()
            ->create();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
        ];

        $data = [
            'usage' => 'test',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/einlegen/in/");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();
        $payload[$name]['location']['item'] = $container->getBarcode();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('.alert-danger');
        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    #[Depends('testStoreActionValidStorageOverride')]
    public function testStoreActionInvalidStorageOverride()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $container = $factory
            ->container()
            ->with(['storageOverride' => false])
            ->create();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'location' => $asset->getLocation(),
        ];

        $data = [
            'usage' => 'test',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/einlegen/in/");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();
        $payload[$name]['location']['item'] = $container->getBarcode();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('.alert-danger');
        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    public function testAssignCaseActionValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $case = CaseFactory::createOne();
        $asset = $factory->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'case' => $asset->getCase(),
        ];

        $data = [
            'usage' => 'test',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/$barcode/in/fall/");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();
        $payload[$name]['case']['item'] = $case->getCaseId();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/$barcode");

        $data['barcode'] = $barcode;
        $data['state'] = State::AssignedCase;
        $data['systemAction'] = false;
        $data['modifiedBy'] = $this->getUser('user');
        $data['case'] = $case->_real();

        // assert correct database changes
        $this->seeInDatabase(AssetHistoryRepository::class, $history);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    #[Depends('testAssignCaseActionValid')]
    public function testAssignCaseActionInvalidAlreadyAssigned()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $case = CaseFactory::createOne();
        $asset = $factory
            ->assignedToCase($case->_real())
            ->with(['state' => State::Used]) // to prevent same state error
            ->create();
        $barcode = $asset->getBarcode();

        $history = [
            'asset' => $asset->_real(),
            'usage' => $asset->getUsage(),
            'state' => $asset->getState(),
            'modifiedBy' => $asset->getModifiedBy(),
            'case' => $asset->getCase(),
        ];

        $this->loginUser($client)->request('GET', "/objekt/$barcode/in/fall/");
        $this->assertResponseRedirects("/objekt/$barcode");
        $client->followRedirect();
        $this->assertSelectorTextContains('.alert', 'asset.state.added_to_case.still_assigned');

        // assert no changes made to database
        $this->assertNoAssetChanges($history, $barcode);
    }

    public function testSaveImageOnDriveActionFromSourceValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $target = $factory->hdd()->create();
        $source = $factory->exhibitHdd()->create();

        $target_history = [
            'asset' => $target->_real(),
            'usage' => $target->getUsage(),
            'state' => $target->getState(),
            'modifiedBy' => $target->getModifiedBy(),
        ];

        $source_history = [
            'asset' => $source->_real(),
            'usage' => $source->getUsage(),
            'state' => $source->getState(),
            'modifiedBy' => $source->getModifiedBy(),
        ];

        $data = [
            'usage' => 'save',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/{$source->getBarcode()}/Asservatenimage/speichern/");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();
        $payload[$name]['image_target']['item'] = $target->getBarcode();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseRedirects("/objekt/{$source->getBarcode()}");

        $source_data = ['usage' => $data['usage']];
        $source_data['barcode'] = $source->getBarcode();
        $source_data['state'] = State::SavedImage;
        $source_data['systemAction'] = false;
        $source_data['modifiedBy'] = $this->getUser('user');

        // assert correct database changes
        $this->seeInDatabase(AssetHistoryRepository::class, $source_history);
        $this->seeInDatabase(AssetRepository::class, $source_data);

        // assert no changes to target
        $this->assertNoAssetChanges($target_history, $target->getBarcode());

        $target = $factory->find($target->getBarcode());
        $source = $factory->find($source->getBarcode());
        // test that target is in source images and vice versa
        $this->assertContains($target->_real(), $source->getHdds());
        $this->assertContains($source->_real(), $target->getImages());
    }

    #[Depends('testSaveImageOnDriveActionFromSourceValid')]
    public function testSaveImageOnDriveActionFromTargetValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $target = $factory->hdd()->create();
        $source = $factory->exhibitHdd()->create();

        $target_history = [
            'asset' => $target->_real(),
            'usage' => $target->getUsage(),
            'state' => $target->getState(),
            'modifiedBy' => $target->getModifiedBy(),
        ];

        $source_history = [
            'asset' => $source->_real(),
            'usage' => $source->getUsage(),
            'state' => $source->getState(),
            'modifiedBy' => $source->getModifiedBy(),
        ];

        $data = [
            'usage' => 'save',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/{$target->getBarcode()}/Asservatenimage/speichern/");

        // assert redirects to overview with search query
        $this->assertResponseRedirects('/objekte?search=c:5');

        $client->followRedirect();

        // assert source is listed
        $this->assertAnySelectorTextContains('.item-row', $source->getName());

        // go to source action
        $crawler = $this->loginUser($client)->request('GET', "/objekt/{$source->getBarcode()}/Asservatenimage/speichern/");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $client->request($form->getMethod(), $form->getUri(), $form->getPhpValues());
        $this->assertResponseRedirects("/objekt/{$source->getBarcode()}");

        $source_data = ['usage' => $data['usage']];
        $source_data['barcode'] = $source->getBarcode();
        $source_data['state'] = State::SavedImage;
        $source_data['systemAction'] = false;
        $source_data['modifiedBy'] = $this->getUser('user');

        // assert correct database changes
        $this->seeInDatabase(AssetHistoryRepository::class, $source_history);
        $this->seeInDatabase(AssetRepository::class, $source_data);

        // assert no changes to target
        $this->assertNoAssetChanges($target_history, $target->getBarcode());

        $target = $factory->find($target->getBarcode());
        $source = $factory->find($source->getBarcode());
        // test that target is in source images and vice versa
        $this->assertContains($target->_real(), $source->getHdds());
        $this->assertContains($source->_real(), $target->getImages());
    }

    #[Depends('testSaveImageOnDriveActionFromSourceValid')]
    public function testSaveImageOnDriveActionFromSourceInvalidTarget()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $target = $factory->container()->create();
        $source = $factory->exhibitHdd()->create();

        $target_history = [
            'asset' => $target->_real(),
            'usage' => $target->getUsage(),
            'state' => $target->getState(),
            'modifiedBy' => $target->getModifiedBy(),
        ];

        $source_history = [
            'asset' => $source->_real(),
            'usage' => $source->getUsage(),
            'state' => $source->getState(),
            'modifiedBy' => $source->getModifiedBy(),
        ];

        $data = [
            'usage' => 'save',
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/{$source->getBarcode()}/Asservatenimage/speichern/");

        // get form
        $name = 'single_action';
        $submit = '[save]';
        $form = $crawler->selectButton($name.$submit)->form();

        // populate form
        $formData = $data;

        // submit form
        $form->setValues([$name => $formData]);
        $payload = $form->getPhpValues();
        $payload[$name]['image_target']['item'] = $target->getBarcode();

        $client->request($form->getMethod(), $form->getUri(), $payload);
        $this->assertResponseIsSuccessful();
        // alert symbol from validation errors
        $this->assertSelectorExists('.alert-danger');

        // assert no changes to target
        $this->assertNoAssetChanges($source_history, $source->getBarcode());
        $this->assertNoAssetChanges($target_history, $target->getBarcode());
    }

    #[Depends('testSaveImageOnDriveActionFromSourceValid')]
    public function testSaveImageOnDriveActionFromTargetInvalidTarget()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $target = $factory->container()->create();

        $target_history = [
            'asset' => $target->_real(),
            'usage' => $target->getUsage(),
            'state' => $target->getState(),
            'modifiedBy' => $target->getModifiedBy(),
        ];

        $crawler = $this->loginUser($client)->request('GET', "/objekt/{$target->getBarcode()}/Asservatenimage/speichern/");
        $this->assertResponseRedirects("/objekt/{$target->getBarcode()}");

        $client->followRedirect();
        // alert symbol from validation errors
        $this->assertSelectorExists('.alert-danger');
        $this->assertNoAssetChanges($target_history, $target->getBarcode());
    }

    public function testAssetScannerValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();

        $asset = $factory->create();

        $crawler = $this->loginUser($client)->request('GET', '/objekte-scanner');

        // get form
        $form = $crawler->filter('form')->form();
        $form->setValues(['form' => ['search' => $asset->getBarcode()]]);

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/{$asset->getBarcode()}");
    }

    #[Depends('testAssetScannerValid')]
    public function testAssetScannerInvalid()
    {
        $client = static::createClient();
        $barcode = 'DTHW55678';

        $crawler = $this->loginUser($client)->request('GET', '/objekte-scanner');

        // get form
        $form = $crawler->filter('form')->form();
        $form->setValues(['form' => ['search' => $barcode]]);

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.alert-danger', 'asset.error.not_found');
    }

    // #[Depends("testSaveImageOnDriveActionFromTargetValid")]
    // #[Depends("testSaveImageOnDriveActionFromSourceValid")]
    // public function testSaveImageOnDriveActionInvalidNotApplicable()
    // {
    //     $client = static::createClient();
    //     $factory = AssetFactory::new();

    //     $asset = $factory->container()->create();
    //     $barcode = $asset->getBarcode();

    //     $history = [
    //         'asset' => $asset->_real(),
    //         'usage' => $asset->getUsage(),
    //         'state' => $asset->getState(),
    //         'modifiedBy' => $asset->getModifiedBy(),
    //     ];

    //     $path = 'Asservatenimage/speichern/';
    //     $this->loginUser($client)->request('GET', "/objekt/$barcode/$path");
    //     $this->assertResponseRedirects("/objekt/$barcode");

    //     // assert no changes made
    //     $this->assertNoAssetChanges($history, $barcode);
    // }

    public function testListCaseOptions()
    {
        $client = static::createClient();
        $factory = CaseFactory::new();
        $uri = '/asset/cases';

        $inactive = $factory->createSequence(
            function () {
                foreach (range(0, 14) as $i) {
                    yield [
                        'caseId' => "Inactive Case $i",
                        'description' => (0 == $i % 2) ? 'yes' : 'no',
                        'openedOn' => \DateTime::createFromFormat('U', $i),
                        'active' => false,
                    ];
                }
            }
        );

        $active = $factory->createSequence(
            function () {
                foreach (range(0, 14) as $i) {
                    yield [
                        'caseId' => "Active Case $i",
                        'description' => (0 == $i % 2) ? 'yes' : 'no',
                        'openedOn' => \DateTime::createFromFormat('U', $i + 1000),
                        'active' => true,
                    ];
                }
            }
        );

        // test plain results
        $result = $this->queryJsonApi($client, $uri, []);
        $this->assertEquals(\count($active), $result['total']);
        $this->assertCount(10, $result['data']);

        // all inactive should not be returned
        foreach ($inactive as $case) {
            $exp = [
                'id' => $case->getId(),
                'caseId' => $case->getCaseId(),
                'description' => $case->getDescription(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // last 10 active cases should occur
        for ($i = 5; $i < 15; ++$i) {
            $case = $active[$i];

            $exp = [
                'id' => $case->getId(),
                'caseId' => $case->getCaseId(),
                'description' => $case->getDescription(),
            ];
            $this->assertContains($exp, $result['data']);
        }

        // first 5 active cases should not occur
        for ($i = 0; $i < 5; ++$i) {
            $case = $active[$i];

            $exp = [
                'id' => $case->getId(),
                'caseId' => $case->getCaseId(),
                'description' => $case->getDescription(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // test limit parameter (only 15 in total)
        $result = $this->queryJsonApi($client, $uri, ['limit' => 25]);
        $this->assertEquals(\count($active), $result['total']);
        $this->assertCount(min([25, \count($active)]), $result['data']);

        // test limit parameter, unkown value
        $result = $this->queryJsonApi($client, $uri, ['limit' => 255]);
        $this->assertEquals(\count($active), $result['total']);
        $this->assertCount(10, $result['data']);

        // test search function
        $result = $this->queryJsonApi($client, $uri, ['query' => 'yes', 'limit' => 50]);
        $this->assertEquals(8, $result['total']);
        $this->assertCount(8, $result['data']);

        // all inactive should not be returned
        foreach ($inactive as $case) {
            $exp = [
                'id' => $case->getId(),
                'caseId' => $case->getCaseId(),
                'description' => $case->getDescription(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // match only even active cases
        for ($i = 0; $i < \count($active); ++$i) {
            $case = $active[$i];

            $exp = [
                'id' => $case->getId(),
                'caseId' => $case->getCaseId(),
                'description' => $case->getDescription(),
            ];
            if (0 == $i % 2) {
                $this->assertContains($exp, $result['data']);
            } else {
                $this->assertNotContains($exp, $result['data']);
            }
        }
    }

    public function testListStorageOptions()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $uri = '/asset/locations';

        $nonStorages = $factory->createSequence(
            function () {
                foreach (range(0, 14) as $i) {
                    yield [
                        'barcode' => 'DTAS'.str_pad($i, 5, '0', STR_PAD_LEFT),
                        'category' => Category::Exhibit,
                        'name' => (0 == $i % 2) ? 'yes' : 'no',
                        'usage' => '',
                        'note' => '',
                        'lastUpdatedOn' => \DateTime::createFromFormat('U', $i),
                        'state' => State::Added,
                        'storageOverride' => false,
                    ];
                }
            }
        );

        $storageOverrides = $factory->record()->createSequence(
            function () {
                foreach (range(0, 14) as $i) {
                    yield [
                        'barcode' => 'DTAK'.str_pad($i, 5, '0', STR_PAD_LEFT),
                        'category' => Category::Record,
                        'name' => (0 == $i % 2) ? 'yes' : 'no',
                        'usage' => '',
                        'note' => '',
                        'lastUpdatedOn' => \DateTime::createFromFormat('U', $i + 1000),
                        'state' => $i >= 5 ? State::Added : State::Lost,
                        'storageOverride' => true,
                    ];
                }
            }
        );

        $storages = $factory->container()->createSequence(
            function () {
                foreach (range(0, 14) as $i) {
                    yield [
                        'barcode' => 'DTAW'.str_pad($i, 5, '0', STR_PAD_LEFT),
                        'category' => Category::Container,
                        'name' => (0 == $i % 2) ? 'yes' : 'no',
                        'usage' => '',
                        'note' => '',
                        'lastUpdatedOn' => \DateTime::createFromFormat('U', $i + 2000),
                        'state' => $i >= 5 ? State::Added : State::Destroyed,
                        'storageOverride' => (0 == $i % 2) ? true : null,
                    ];
                }
            }
        );

        // test plain results
        $result = $this->queryJsonApi($client, $uri, []);
        $this->assertCount(10, $result['data']);
        $this->assertEquals(20, $result['total']);

        // all non storages should not be returned
        foreach ($nonStorages as $asset) {
            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // match last 10 storagess
        for ($i = 5; $i < 15; ++$i) {
            $asset = $storages[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertContains($exp, $result['data']);
        }

        // dont match rest
        for ($i = 0; $i < 5; ++$i) {
            $asset = $storages[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }
        for ($i = 0; $i < 15; ++$i) {
            $asset = $storageOverrides[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // test limit parameter (only 20 in total)
        $result = $this->queryJsonApi($client, $uri, ['limit' => 25]);
        $this->assertEquals(20, $result['total']);
        $this->assertCount(20, $result['data']);

        // test limit parameter, unkown value
        $result = $this->queryJsonApi($client, $uri, ['limit' => 255]);
        $this->assertEquals(20, $result['total']);
        $this->assertCount(10, $result['data']);

        // test search function
        $result = $this->queryJsonApi($client, $uri, ['query' => 'yes', 'limit' => 50]);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['data']);

        // all non storages should not be returned
        foreach ($nonStorages as $asset) {
            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // match 5 storage overrides
        for ($i = 0; $i < 15; ++$i) {
            $asset = $storageOverrides[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];

            if ($i < 5 || 0 != $i % 2) {
                $this->assertNotContains($exp, $result['data']);
            } else {
                $this->assertContains($exp, $result['data']);
            }
        }
        // match 5 storages
        for ($i = 0; $i < 15; ++$i) {
            $asset = $storages[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];

            if ($i < 5 || 0 != $i % 2) {
                $this->assertNotContains($exp, $result['data']);
            } else {
                $this->assertContains($exp, $result['data']);
            }
        }
    }

    public function testListHddImageTargets()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $uri = '/asset/image_targets';

        $nonTargets = $factory->createSequence(
            function () {
                foreach (range(0, 14) as $i) {
                    yield [
                        'barcode' => 'DTAS'.str_pad($i, 5, '0', STR_PAD_LEFT),
                        'category' => Category::Exhibit,
                        'name' => (0 == $i % 2) ? 'yes' : 'no',
                        'usage' => '',
                        'note' => '',
                        'lastUpdatedOn' => \DateTime::createFromFormat('U', $i),
                        'state' => State::Added,
                        'storageOverride' => false,
                    ];
                }
            }
        );

        $targets = $factory->container()->createSequence(
            function () {
                foreach (range(0, 19) as $i) {
                    yield [
                        'barcode' => 'DTHD'.str_pad($i, 5, '0', STR_PAD_LEFT),
                        'category' => Category::Hdd,
                        'name' => (0 == $i % 2) ? 'yes' : 'no',
                        'usage' => '',
                        'note' => '',
                        'lastUpdatedOn' => \DateTime::createFromFormat('U', $i + 1000),
                        'state' => $i < 10 ? State::Added : State::Destroyed,
                    ];
                }
            }
        );

        // test plain results
        $result = $this->queryJsonApi($client, $uri, []);
        $this->assertCount(10, $result['data']);
        $this->assertEquals(10, $result['total']);

        // all non targets should not be returned
        foreach ($nonTargets as $asset) {
            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // match last 10 targetss
        for ($i = 0; $i < 10; ++$i) {
            $asset = $targets[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertContains($exp, $result['data']);
        }

        // dont match rest
        for ($i = 10; $i < 20; ++$i) {
            $asset = $targets[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // test limit parameter (only 20 in total)
        $result = $this->queryJsonApi($client, $uri, ['limit' => 25]);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['data']);

        // test limit parameter, unkown value
        $result = $this->queryJsonApi($client, $uri, ['limit' => 255]);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['data']);

        // test search function
        $result = $this->queryJsonApi($client, $uri, ['query' => 'yes', 'limit' => 50]);
        $this->assertEquals(5, $result['total']);
        $this->assertCount(5, $result['data']);

        // all non targets should not be returned
        foreach ($nonTargets as $asset) {
            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // match 5 targets
        for ($i = 0; $i < 20; ++$i) {
            $asset = $targets[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];

            if ($i < 10 && 0 == $i % 2) {
                $this->assertContains($exp, $result['data']);
            } else {
                $this->assertNotContains($exp, $result['data']);
            }
        }
    }

    public function testListHddImageSources()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $uri = '/asset/image_sources';

        $nonSources = $factory->createSequence(
            function () {
                foreach (range(0, 14) as $i) {
                    yield [
                        'barcode' => 'DTAK'.str_pad($i, 5, '0', STR_PAD_LEFT),
                        'category' => Category::Record,
                        'name' => (0 == $i % 2) ? 'yes' : 'no',
                        'usage' => '',
                        'note' => '',
                        'lastUpdatedOn' => \DateTime::createFromFormat('U', $i),
                        'state' => State::Added,
                        'storageOverride' => false,
                    ];
                }
            }
        );

        $sources = $factory->container()->createSequence(
            function () {
                foreach (range(0, 19) as $i) {
                    yield [
                        'barcode' => 'DTAS'.str_pad($i, 5, '0', STR_PAD_LEFT),
                        'category' => Category::ExhibitHdd,
                        'name' => (0 == $i % 2) ? 'yes' : 'no',
                        'usage' => '',
                        'note' => '',
                        'lastUpdatedOn' => \DateTime::createFromFormat('U', $i + 1000),
                        'state' => $i < 10 ? State::Added : State::Destroyed,
                    ];
                }
            }
        );

        // test plain results
        $result = $this->queryJsonApi($client, $uri, []);
        $this->assertCount(10, $result['data']);
        $this->assertEquals(10, $result['total']);

        // all non sources should not be returned
        foreach ($nonSources as $asset) {
            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // match last 10 sourcess
        for ($i = 0; $i < 10; ++$i) {
            $asset = $sources[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertContains($exp, $result['data']);
        }

        // dont match rest
        for ($i = 10; $i < 20; ++$i) {
            $asset = $sources[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // test limit parameter (only 20 in total)
        $result = $this->queryJsonApi($client, $uri, ['limit' => 25]);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['data']);

        // test limit parameter, unkown value
        $result = $this->queryJsonApi($client, $uri, ['limit' => 255]);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['data']);

        // test search function
        $result = $this->queryJsonApi($client, $uri, ['query' => 'yes', 'limit' => 50]);
        $this->assertEquals(5, $result['total']);
        $this->assertCount(5, $result['data']);

        // all non sources should not be returned
        foreach ($nonSources as $asset) {
            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
            $this->assertNotContains($exp, $result['data']);
        }

        // match 5 sources
        for ($i = 0; $i < 20; ++$i) {
            $asset = $sources[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];

            if ($i < 10 && 0 == $i % 2) {
                $this->assertContains($exp, $result['data']);
            } else {
                $this->assertNotContains($exp, $result['data']);
            }
        }
    }

    public function testGetAssets()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $uri = '/asset/assets';

        $assets = $factory->createSequence(
            function () {
                foreach (range(0, 19) as $i) {
                    yield [
                        'name' => (0 == $i % 2) ? 'yes' : 'no',
                        'usage' => '',
                        'note' => '',
                        'lastUpdatedOn' => \DateTime::createFromFormat('U', $i),
                    ];
                }
            }
        );

        // test plain results
        $result = $this->queryJsonApi($client, $uri, []);
        $this->assertEquals(20, $result['total']);
        $this->assertCount(10, $result['data']);

        // test limit parameter (only 20 in total)
        $result = $this->queryJsonApi($client, $uri, ['limit' => 25]);
        $this->assertEquals(20, $result['total']);
        $this->assertCount(20, $result['data']);

        // test limit parameter, unkown value
        $result = $this->queryJsonApi($client, $uri, ['limit' => 255]);
        $this->assertEquals(20, $result['total']);
        $this->assertCount(10, $result['data']);

        // test search function
        $result = $this->queryJsonApi($client, $uri, ['query' => 'yes', 'limit' => 50]);
        $this->assertEquals(10, $result['total']);
        $this->assertCount(10, $result['data']);

        // match 5 sources
        for ($i = 0; $i < 20; ++$i) {
            $asset = $assets[$i];

            $exp = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->toTranslatableString(),
                'categoryColor' => $asset->getCategory()->bootstrapColor(),
                'state' => $asset->getState()->toTranslatableString(),
                'stateColor' => $asset->getState()->bootstrapColor(),
                'name' => $asset->getName(),
            ];

            if (0 == $i % 2) {
                $this->assertContains($exp, $result['data']);
            } else {
                $this->assertNotContains($exp, $result['data']);
            }
        }
    }

    // first go to asset overview
    // select 5 then and redirect to action view
    // select 5 more and perform action
    public function testMultiActionAllValid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $uri = '/asset/assets';

        $assets = $factory->many(10)->applyStateMethod('hdd')->create();
        $histories = [];
        foreach ($assets as $asset) {
            $histories[] = [
                'asset' => $asset->_real(),
                'usage' => $asset->getUsage(),
                'state' => $asset->getState(),
                'modifiedBy' => $asset->getModifiedBy(),
            ];
        }

        $crawler = $this->loginUser($client)->request('GET', '/objekte', ['limit' => 25]);
        $form = $crawler->selectButton('multi_action[preview]')->form();

        $data = [
            'action' => 0, // null
            'assets' => [],
        ];

        // select first 5 assets
        for ($i = 0; $i < 5; ++$i) {
            $data['assets'][] = $assets[$i]->getBarcode();
        }

        $crawler = $client->request($form->getMethod(), $form->getUri(), ['multi_action' => $data]);
        $this->assertResponseIsSuccessful();

        // check for no violations
        // $this->assertSelectorNotExists('.glyphicon-exclamation-sign');

        $form = $crawler->selectButton('multi_action[save]')->form();
        $data = $form->getPhpValues();

        // check first 5 assets are selected
        for ($i = 0; $i < 5; ++$i) {
            $this->assertContains($assets[$i]->getBarcode(), $data['multi_action']['assets']);
        }

        $data['multi_action']['lastUpdatePerformedOn'] = (new \DateTime())->format('Y-m-d H:i:s');
        $data['multi_action']['usage'] = 'Test usage';

        // select all 10 assets
        for ($i = 5; $i < 10; ++$i) {
            $data['multi_action']['assets'][] = $assets[$i]->getBarcode();
        }

        $crawler = $client->request($form->getMethod(), $form->getUri(), $data);
        $this->assertResponseRedirects('/objekte');

        $testdata = [
            'usage' => $data['multi_action']['usage'],
        ];

        for ($i = 0; $i < 10; ++$i) {
            $asset = $assets[$i];
            $testdata['barcode'] = $asset->getBarcode();
            $testdata['state'] = State::Cleaned;
            $testdata['systemAction'] = false;
            $testdata['modifiedBy'] = $this->getUser('user');

            // check history
            $this->seeInDatabase(AssetHistoryRepository::class, $histories[$i]);
            $this->seeInDatabase(AssetRepository::class, $testdata);
        }
    }

    /**
     * first go to asset overview
     * select 5 then and redirect to action view
     * select 5 more and perform action.
     */
    #[Depends('testMultiActionAllValid')]
    public function testMultiActionSomeInvalid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $uri = '/asset/assets';

        $histories = [];
        $hdds = $factory->many(5)->applyStateMethod('hdd')->create();
        foreach ($hdds as $asset) {
            $histories[] = [
                'asset' => $asset->_real(),
                'usage' => $asset->getUsage(),
                'state' => $asset->getState(),
                'modifiedBy' => $asset->getModifiedBy(),
            ];
        }

        $records = $factory->many(5)->applyStateMethod('record')->create();
        foreach ($records as $asset) {
            $histories[] = [
                'asset' => $asset->_real(),
                'usage' => $asset->getUsage(),
                'state' => $asset->getState(),
                'modifiedBy' => $asset->getModifiedBy(),
            ];
        }

        $crawler = $this->loginUser($client)->request('GET', '/objekte', ['limit' => 25]);
        $form = $crawler->selectButton('multi_action[preview]')->form();

        $data = [
            'action' => 0, // null
            'assets' => [],
        ];

        // select one hdd and one non-hdd assets
        $data['assets'][] = $hdds[0]->getBarcode();
        $data['assets'][] = $records[0]->getBarcode();

        $crawler = $client->request($form->getMethod(), $form->getUri(), ['multi_action' => $data]);
        $this->assertResponseIsSuccessful();

        // check for violations
        $this->assertSelectorExists('.glyphicon-exclamation-sign');

        $form = $crawler->selectButton('multi_action[save]')->form();
        $data = $form->getPhpValues();

        // check first 5 assets are selected
        $this->assertContains($hdds[0]->getBarcode(), $data['multi_action']['assets']);
        $this->assertContains($records[0]->getBarcode(), $data['multi_action']['assets']);

        $data['multi_action']['lastUpdatePerformedOn'] = (new \DateTime())->format('Y-m-d H:i:s');
        $data['multi_action']['usage'] = 'Test usage';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $data);
        $this->assertResponseIsSuccessful();

        $this->assertNoAssetChanges($histories[0], $hdds[0]->getBarcode());
        $this->assertNoAssetChanges($histories[5], $records[0]->getBarcode());
    }

    /**
     * try to assign case to asset where one asset already has a case
     * asssigned but bypasses state validation.
     */
    public function testMultiActionCaseAlreadyAssignedInvalid()
    {
        $client = static::createClient();
        $factory = AssetFactory::new();
        $caseFactory = CaseFactory::new();
        $uri = '/asset/assets';

        $ogCase = $caseFactory->create();
        $ewCase = $caseFactory->create();

        $histories = [];

        // create one asset with case and one without
        $with = $factory->with([
            'state' => State::Edited,
            'case' => $ogCase->_real(),
        ])->create();
        $without = $factory->create();

        $with_h = [
            'asset' => $with->_real(),
            'usage' => $with->getUsage(),
            'state' => $with->getState(),
            'case' => $with->getCase(),
        ];

        $without_h = [
            'asset' => $without->_real(),
            'usage' => $without->getUsage(),
            'state' => $without->getState(),
            'case' => $without->getCase(),
        ];

        $crawler = $this->loginUser($client)->request('GET', '/objekte', ['limit' => 25]);
        $form = $crawler->selectButton('multi_action[preview]')->form();

        $data = [
            'action' => 8, // assign case
            'assets' => [],
        ];

        // select one hdd and one non-hdd assets
        $data['assets'][] = $with->getBarcode();
        $data['assets'][] = $without->getBarcode();

        $crawler = $client->request($form->getMethod(), $form->getUri(), ['multi_action' => $data]);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('multi_action[save]')->form();
        $data = $form->getPhpValues();

        // check assets are selected
        $this->assertContains($with->getBarcode(), $data['multi_action']['assets']);
        $this->assertContains($without->getBarcode(), $data['multi_action']['assets']);

        $data['multi_action']['lastUpdatePerformedOn'] = (new \DateTime())->format('Y-m-d H:i:s');
        $data['multi_action']['usage'] = 'Test usage';
        $data['multi_action']['case']['item'] = $ewCase->getCaseId();

        $crawler = $client->request($form->getMethod(), $form->getUri(), $data);
        $this->assertResponseIsSuccessful();

        // check for violations
        $this->assertSelectorExists('.glyphicon-exclamation-sign');

        $this->assertNoAssetChanges($with_h, $with->getBarcode());
        $this->assertNoAssetChanges($without_h, $without->getBarcode());

        // now retry only with "without" asset
        $data['multi_action']['assets'] = [$without->getBarcode()];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $data);
        $this->assertResponseRedirects('/objekte');
    }

    /**
     * Assert no changes made to database for the given set aka
     * no history entry exists but an asset entry.
     */
    private function assertNoAssetChanges($data, $barcode)
    {
        $this->dontSeeInDatabase(AssetHistoryRepository::class, $data);
        $data['barcode'] = $barcode;
        unset($data['asset']);
        $this->seeInDatabase(AssetRepository::class, $data);
    }

    /**
     * Send request to list json api and return json decoded data.
     */
    private function queryJsonApi($client, $uri, $params)
    {
        $this->loginUser($client)->request('GET', $uri, $params);
        $this->assertResponseIsSuccessful();

        $response = $client->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);

        return json_decode($response->getContent(), true);
    }
}
