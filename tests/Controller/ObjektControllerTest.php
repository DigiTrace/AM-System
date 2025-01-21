<?php

namespace App\Tests\Controller;

use App\Repository\DatentraegerRepository;
use App\Repository\ObjektRepository;
use App\Tests\_support\BaseWebTestCase;
use App\Tests\Factory\ObjektFactory;

/**
 * @author Ben Brooksnieder
 */
class ObjektControllerTest extends BaseWebTestCase
{
    public function genericUrlProvider()
    {
        yield ['/objekte/faq'];
        yield ['/objekte'];
        yield ['/objekt-scanner'];
        yield ['/objekt/anlegen'];
        yield ['/objekte/aendern/'];
        // yield ['/objekte/aendern/in'];
    }

    public function detailUrlProvider()
    {
        yield ['/objekt/%s'];
        yield ['/objekt/%s/editieren'];
        yield ['/objekt/%s/nullen'];
        yield ['/objekt/%s/verwenden'];
        yield ['/objekt/%s/vernichtet'];
        yield ['/objekt/%s/uebergeben'];
        yield ['/objekt/%s/verloren'];
        yield ['/objekt/%s/reservieren'];
        // yield ['/objekt/%s/reservierung/aufheben'];
        // yield ['/objekt/%s/entnehmen'];
        // yield ['/objekt/%s/aus/Fall/entfernen'];
        yield ['/objekt/%s/neutralisieren'];
        yield ['/objekt/%s/einlegen/in'];
        yield ['/objekt/%s/in/fall'];
        yield ['/objekt/%s/upload'];
        // yield ['/objekt/%s/Asservatenimage/speichern/'];
        // yield ['/objekt/{fromid}/einlegen/in/{toid}'];
        // yield ['/objekt/{objectid}/in/fall/{caseid}/hinzufuegen'];
        // yield ['/objekt/{fromid}/Asservatenimage/speichern/von/{toid}/{returnid}'];
    }

    public function validObjektProvider()
    {
        yield [[
            'barcode_id' => 'DTAS00001',
            'name' => '(TEST)Hitachi Festplatte',
            'verwendung' => '(TEST)Sind gelöschte Beweise drauf',
            'kategorie_id' => '0', // 'kategorie' => 'category.exhibit',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTHW00007',
            'name' => '(TEST)Thinkpad e330',
            'verwendung' => '(TEST)Ediscovery',
            'kategorie_id' => '1', // 'kategorie' => 'category.equipment',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTHW00001',
            'name' => '(TEST)Encase Koffer mit speziffischen Inhalt',
            'verwendung' => null,
            'kategorie_id' => '1', // 'kategorie' => 'category.equipment',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTHW00002',
            'name' => '(TEST)Schrank',
            'verwendung' => '(TEST)Wird zum Lagern von Asservaten gebraucht',
            'kategorie_id' => 2, // 'kategorie' => 'category.container',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTHW00003',
            'name' => '(TEST)Papierbox',
            'verwendung' => '(TEST)Wird zum Lagern von HDDs gebraucht',
            'kategorie_id' => 2, // 'kategorie' => 'category.container',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTHW00004',
            'name' => '(TEST)Peli Case',
            'verwendung' => '(TEST)Für Mobilen Einsatz',
            'kategorie_id' => 2, // 'kategorie' => 'category.container',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTHW00005',
            'name' => '(TEST)Pappkarton',
            'verwendung' => '(TEST)Zwecks Dringlichkeit in das System eingetragen, nicht im Regen stehen lassen',
            'kategorie_id' => 2, // 'kategorie' => 'category.container',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTAS00002',
            'name' => '(TEST)Selbstbau Rechner i7 mit Nvidia GTX 970 SLI',
            'verwendung' => '(TEST)Eine remote Bitcoinsoftware wurde installiert und auf ein unbekannte Konto gemint',
            'kategorie_id' => 0, // 'kategorie' => 'category.exhibit',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTAS00003',
            'name' => '(TEST)NAS Server QNAP Server',
            'verwendung' => '(TEST)Es wurden Spuren von KiPo Material gefunden',
            'kategorie_id' => 0, // 'kategorie' => 'category.exhibit',
            // 'status_id' => 0,
        ]];
        yield [[
            'barcode_id' => 'DTHW00006',
            'name' => '(TEST)Werkzeugregal',
            'verwendung' => '(TEST)Von einem Schwedischen Versandhandel besorgt',
            'kategorie_id' => 2, // 'kategorie' => 'category.container',
            // 'status_id' => 0,
        ]];
    }

    public function validObjektHDDProvider()
    {
        yield [[
            'barcode_id' => 'DTHD00021',
            'name' => '(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte',
            'verwendung' => '(TEST)Wird für Ein Asservat benötigt',
            'kategorie_id' => '3', // 'kategorie' => 'category.hdd',
            // 'status_id' => 0,
        ], [
            'bauart' => null,
            'formfaktor' => null,
            'groesse' => null,
            'groessealt' => null,
            'modell' => null,
            'hersteller' => null,
            'sn' => null,
            'pn' => null,
        ]];
        yield [[
            'barcode_id' => 'DTHD00022',
            'name' => '(TEST)Toshiba 500GB',
            'verwendung' => '(TEST)Austauschplatte Für den Server',
            'kategorie_id' => '3', // 'kategorie' => 'category.hdd',
            // 'status_id' => 0,
        ], [
            'bauart' => 'intern',
            'formfaktor' => '3,5',
            'groesse' => '500',
            'groessealt' => '500',
            'modell' => 'Modell 1',
            'hersteller' => 'Toshiba',
            'sn' => '89437809756B',
            'pn' => 'GHII9',
            'anschluss' => 'SATA',
        ]];
        yield [[
            'barcode_id' => 'DTHD00023',
            'name' => '(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte',
            'verwendung' => '(TEST)Notfallplatte für Forensikkoffer',
            'kategorie_id' => '3', // 'kategorie' => 'category.hdd',
            // 'status_id' => 0,
        ], [
            'bauart' => 'extern',
            'formfaktor' => '2,5',
            'groesse' => '2000',
            'groessealt' => '2000',
            'modell' => 'T00JH9II',
            'hersteller' => 'Toshiba',
            'sn' => '89437809756C',
            'pn' => 'GHII9',
            'anschluss' => 'USB',
        ]];
        yield [[
            'barcode_id' => 'DTHD00020',
            'name' => '(TEST)WD 256 GB 3.5 Zoll externe Intern',
            'verwendung' => '(TEST)Gefunden aus einem älteren Rechner',
            'kategorie_id' => '3', // 'kategorie' => 'category.hdd',
            // 'status_id' => 0,
        ], [
            'bauart' => 'intern',
            'formfaktor' => '3,5',
            'groesse' => '2000',
            'groessealt' => '2000',
            'modell' => 'WD4AU0078',
            'hersteller' => 'WD',
            'sn' => '6777886546',
            'pn' => 'KlllU',
            'anschluss' => 'SATA',
        ]];
        yield [[
            'barcode_id' => 'DTHD00024',
            'name' => '(TEST)Hitachi Ultrastar 1TB',
            'verwendung' => null,
            'kategorie_id' => '3', // 'kategorie' => 'category.hdd',
            // 'status_id' => 0,
        ], [
            'bauart' => 'intern',
            'formfaktor' => '3,5',
            'groesse' => '1000',
            'groessealt' => '1000',
            'modell' => 'K900',
            'hersteller' => 'Hitachi',
            'sn' => '7765398176',
            'pn' => 'ABCDFG',
            'anschluss' => 'SATA',
        ]];
        yield [[
            'barcode_id' => 'DTHD00025',
            'name' => '(TEST)Hitachi Ultrastar 2TB',
            'verwendung' => null,
            'kategorie_id' => '3', // 'kategorie' => 'category.hdd',
            // 'status_id' => 0,
        ], [
            'bauart' => 'intern',
            'formfaktor' => '3,5',
            'groesse' => '2000',
            'groessealt' => '2000',
            'modell' => 'K900',
            'hersteller' => 'Hitachi',
            'sn' => '3234512322',
            'pn' => 'GFEDCA',
            'anschluss' => 'SATA',
        ]];
        yield [[
            'barcode_id' => 'DTAS00004',
            'name' => '(TEST)Intel SSD 430 256GB',
            'verwendung' => null,
            'kategorie_id' => '5', // 'kategorie' => 'category.exhibit.hdd',
            // 'status_id' => 0,
        ], [
            'bauart' => 'intern',
            'formfaktor' => '2,5',
            'groesse' => '250',
            'groessealt' => '256',
            'modell' => '430',
            'hersteller' => 'Intel',
            'sn' => '3344556677',
            'pn' => 'JUHGFDGHK',
            'anschluss' => 'SATA',
        ]];
        yield [[
            'barcode_id' => 'DTAS00005',
            'name' => '(TEST)Hitachi 20 GB hdd',
            'verwendung' => 'Befinden sich verschüsselte Daten',
            'kategorie_id' => '5', // 'kategorie' => 'category.exhibit.hdd',
            // 'status_id' => 0,
        ], [
            'bauart' => 'intern',
            'formfaktor' => '3,5',
            'groesse' => '16',
            'groessealt' => '20',
            'modell' => 'oldware',
            'hersteller' => 'Hitachi',
            'sn' => '123321123',
            'pn' => 'UJHTNMLOI',
            'anschluss' => 'ATA',
        ]];
    }

    public function invalidObjektProvider()
    {
        yield 'Invalid barcode prefix' => [[
            'barcode_id' => 'DKHA00089',
            'name' => '(TEST) Manipulierter Post request 1',
            'verwendung' => '(TEST) soll Schaden verursachen',
            'kategorie_id' => '0',
        ]];
        yield 'Barcode to short' => [[
            'barcode_id' => 'DTAS002',
            'name' => '(TEST)Falsch eingebenenes Objekt',
            'verwendung' => '(TEST)Verwendungszweck ist nicht nötigt',
            'kategorie_id' => '0',
        ]];
        yield 'Barcode to long' => [[
            'barcode_id' => 'DTAS002355',
            'name' => '(TEST)Falsch eingebenenes Objekt',
            'verwendung' => '(TEST)Verwendungszweck ist nicht nötigt',
            'kategorie_id' => '0',
        ]];
        yield 'Prefix dont match category' => [[
            'barcode_id' => 'DTAS00235',
            'name' => '(TEST)Das Asservat soll als Ausrüstung kategorisiert werden',
            'verwendung' => '(TEST)Es wäre nicht gut, wenn dies so umgesetzt wird',
            'kategorie_id' => '1',
        ]];
        // yield 'Missing name' => [[
        //     'barcode_id' => 'DTHD00004',
        //     'name' => '',
        //     'verwendung' => '(TEST)Verwendungszweck ist nicht nötigt',
        //     'kategorie_id' => '3',
        // ]];
    }

    /**
     * @dataProvider genericUrlProvider
     * @dataProvider detailUrlProvider
     */
    public function testProtectedUrls($url)
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    /**
     * @dataProvider genericUrlProvider
     */
    public function testGenericUrls($url)
    {
        $client = static::createClient();
        $this->loginUser($client)->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    /**
     * @dataProvider detailUrlProvider
     */
    public function testDetailUrls($url)
    {
        $factory = ObjektFactory::new();
        $obj = $factory->hdd()->create();

        $client = static::createClient();
        $this->loginUser($client)->request('GET', sprintf($url, $obj->getBarcode()));

        $this->assertResponseIsSuccessful();
    }

    /**
     * @dataProvider validObjektProvider
     * @dataProvider validObjektHDDProvider
     */
    public function testAddObjektValid($objekt, $datentraeger = null)
    {
        // setup
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // make form request
        $form = $crawler->selectButton('add_object[save]')->form();

        // add objekt parameters
        foreach ($objekt as $key => $value) {
            $form["add_object[{$key}]"] = $value ?? '';
        }

        // add datentraeger paramters
        foreach ($datentraeger ?? [] as $key => $value) {
            $form["add_object[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseRedirects("/objekt/{$objekt['barcode_id']}");

        // check database for correct entry
        $this->seeInDatabase(ObjektRepository::class, $objekt);
        if (null !== $datentraeger) {
            $datentraeger['barcode_id'] = $objekt['barcode_id'];
            // copy alt size
            $altSize = $datentraeger['groessealt'];
            unset($datentraeger['groessealt']);
            // test that alt size overwrote size
            if (!empty($altSize) && $altSize != $datentraeger['groesse']) {
                $this->dontSeeInDatabase(DatentraegerRepository::class, $datentraeger);
                $datentraeger['groesse'] = $altSize;
            }
            $this->seeInDatabase(DatentraegerRepository::class, $datentraeger);
        }

        // test overview page
        $this->assertInOverviewPageCorrect($client, $objekt);

        // test details page
        $this->assertDetailsPageCorrect($client, $objekt, $datentraeger);

        return $client;
    }

    /**
     * @dataProvider invalidObjektProvider
     *
     * @depends testAddObjektValid
     */
    public function testAddObjektInvalid($objekt, $datentraeger = null)
    {
        // setup
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // make form request
        $form = $crawler->selectButton('add_object[save]')->form();

        // add objekt parameters
        foreach ($objekt as $key => $value) {
            $form["add_object[{$key}]"] = $value ?? '';
        }

        // add datentraeger paramters
        foreach ($datentraeger ?? [] as $key => $value) {
            $form["add_object[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
        $this->assertSelectorTextContains('div.alert-danger', 'object.is.not.saved');

        // check database for no entrys created
        $this->dontSeeInDatabase(ObjektRepository::class, $objekt);
        if (null !== $datentraeger) {
            $datentraeger['barcode_id'] = $objekt['barcode_id'];
            $this->dontSeeInDatabase(DatentraegerRepository::class, $datentraeger);
        }

        return $client;
    }

    /**
     * @depends testAddObjektInvalid
     */
    public function testAddObjektDuplicate()
    {
        // setup
        $objekt = [
            'barcode_id' => 'DTHW00009',
            'name' => '(TEST)Lenovo im Doppelpack',
            'verwendung' => 'Ein Objekt sollte nicht 2 mal vorkommen',
            'kategorie_id' => '1',
        ];

        // add case first time successful
        $client = $this->testAddObjektValid($objekt);
        $crawler = $this->loginUser($client)->request('GET', '/objekt/anlegen');

        // try to add second time
        $form = $crawler->selectButton('add_object[save]')->form();

        // add objekt parameters
        foreach ($objekt as $key => $value) {
            $form["add_object[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-danger', 'barcode.already.exists.in.database');

        // check database for no entrys created
        $this->seeInDatabase(ObjektRepository::class, $objekt, 1);

        return $client;
    }

    public function testNullObjekt()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->hdd()->create();

        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/nullen");
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, ['form[verwendung]' => 'Aus Testzwecken wird dieses Objekt genullt deklariert']);
        $this->assertResponseRedirects("/objekt/{$objekt->getBarcodeId()}");

        // test overview page
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $objekt->getBarcodeId(),
            'name' => $objekt->getName(),
        ]);

        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $objekt->getBarcodeId(),
            'name' => $objekt->getName(),
            'verwendung' => $objekt->getVerwendung(),
            'kategorie' => $objekt->getKategorie(),
        ]);
        // also test whether it was nulled
        $this->assertSelectorTextContains('#currentstatus', 'status.cleaned');
    }

    protected function assertInOverviewPageCorrect($client, $objekt)
    {
        $crawler = $client->request('GET', '/objekte');
        $this->assertSelectorTextContains("tr:contains('{$objekt['barcode_id']}')", $objekt['barcode_id']);
        $this->assertSelectorTextContains("tr:contains('{$objekt['barcode_id']}')", $objekt['name']);

        return $crawler;
    }

    protected function assertDetailsPageCorrect($client, $objekt, $datentraeger = null)
    {
        $crawler = $client->request('GET', "/objekt/{$objekt['barcode_id']}");
        foreach (['verwendung', 'name', 'kategorie'] as $index) {
            if (!empty($objekt[$index])) {
                $this->assertSelectorTextContains('#currentstatus', $objekt[$index]);
            }
        }

        if (null !== $datentraeger) {
            foreach (['bauart', 'hersteller', 'formfaktor', 'modell', 'sn', 'pn', 'anschluss'] as $index) {
                if (!empty($datentraeger[$index])) {
                    $this->assertSelectorTextContains('#hddinfo', $datentraeger[$index]);
                }
            }
        }

        return $crawler;
    }
}
