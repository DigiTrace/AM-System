<?php

namespace App\Tests\Controller;

use App\Entity\Objekt;
use App\Repository\DatentraegerRepository;
use App\Repository\ObjektRepository;
use App\Tests\_support\BaseWebTestCase;
use App\Tests\Factory\DatentraegerFactory;
use App\Tests\Factory\FallFactory;
use App\Tests\Factory\ObjektFactory;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Ben Brooksnieder
 */
class ObjektControllerTest extends BaseWebTestCase
{
    //
    // ================ DATA PROVIDERS ================
    //

    public function genericUrlProvider()
    {
        yield ['/objekte/faq'];
        yield ['/objekte'];
        yield ['/objekte-scanner'];
        yield ['/objekt/anlegen'];
        yield ['/objekte/aendern/'];
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
        yield ['/objekt/%s/neutralisieren'];
        yield ['/objekt/%s/einlegen/in'];
        yield ['/objekt/%s/in/fall'];
        yield ['/objekt/%s/upload'];
        yield ['/objekt/%s/Asservatenimage/speichern/'];
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
            'verwendung' => '(TEST)Edriveovery',
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
            'verwendung' => '(TEST)Von einem Schwedrivehen Versandhandel besorgt',
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
        yield 'Missing name' => [[
            'barcode_id' => 'DTHD00004',
            'name' => '',
            'verwendung' => '(TEST)Verwendungszweck ist nicht nötigt',
            'kategorie_id' => '3',
        ]];
    }

    /**
     * @todo implement history queries
     */
    public function simpleSearchParamsProvider()
    {
        yield 'Category query' => [
            'query' => "c:". Objekt::KATEGORIE_ASSERVAT, // 0
            'expected_results' => 1,
        ];
        yield 'Status query' => [
            'query' => "s:". Objekt::STATUS_GENULLT, // 1
            'expected_results' => 1,
        ];
        yield 'Barcode query' => [
            'query' => "barcode:DTHD12345",
            'expected_results' => 1,
        ];
        yield 'Name query' => [
            'query' => "name:name_string",
            'expected_results' => 1,
        ];
        yield 'Note query' => [
            'query' => "note:note_string",
            'expected_results' => 1,
        ];
        yield 'Description query' => [
            'query' => "desc:current_description",
            'expected_results' => 2,
        ];
        // yield 'Previous description query' => [
        //     'query' => "hdesc:current_description",
        //     'expected_results' => 2,
        // ];
        yield 'Reservation query (name)' => [
            'query' => "r:current_reservation",
            'expected_results' => 1,
        ];
        yield 'Reservation query (not reserved)' => [
            'query' => "r:false",
            'expected_results' => 4,
        ];
        yield 'Reservation query (reserved)' => [
            'query' => "r:true",
            'expected_results' => 1,
        ];
        // yield 'Previous reservation query (name)' => [
        //     'query' => "hr:current_reservation",
        //     'expected_results' => 1,
        // ];
        // yield 'Last update query' => [
        //     'query' => "mu:?",
        //     'expected_results' => 1,
        // ];
        // yield 'Prior update query' => [
        //     'query' => "hu:?",
        //     'expected_results' => 1,
        // ];
        yield 'Stored in query' => [
            'query' => "l:DTHW33344",
            'expected_results' => 1,
        ];
        // yield 'Previous stored in query' => [
        //     'query' => "hstoredin:DTHW33344",
        //     'expected_results' => 1,
        // ];
        yield 'Case query' => [
            'query' => "case:case_id",
            'expected_results' => 1,
        ];
        // yield 'Previos case query' => [
        //     'query' => "hcase:case_id",
        //     'expected_results' => 1,
        // ];
        yield 'Active case query' => [
            'query' => "caseactive:true",
            'expected_results' => 1,
        ];
        yield 'Drive type query' => [
            'query' => "type:extern",
            'expected_results' => 2,
        ];
        yield 'Drive form query' => [
            'query' => "ff:true",
            'expected_results' => 2,
        ];
        yield 'Drive size query' => [
            'query' => "size:666",
            'expected_results' => 1,
        ];
        yield 'Drive prod query' => [
            'query' => "prod:prod_string",
            'expected_results' => 1,
        ];
        yield 'Drive model query' => [
            'query' => "modell:modell_string",
            'expected_results' => 1,
        ];
        yield 'Drive connection query' => [
            'query' => "connection:SATA",
            'expected_results' => 1,
        ];
        yield 'Drive serial number query' => [
            'query' => "sn:9988776655_sn",
            'expected_results' => 1,
        ];
        yield 'Drive product number query' => [
            'query' => "pn:1122334400_pn",
            'expected_results' => 1,
        ];
        yield 'Date query (exact)' => [
            'query' => "d:24.02.2042",
            'expected_results' => 1,
        ];
        yield 'Date query (older)' => [
            'query' => "d:<10.02.1970",
            'expected_results' => 1,
        ];
        yield 'Date query (newer)' => [
            'query' => "d:>17.03.2040",
            'expected_results' => 2,
        ];
    }

    public function complexSearchParamsProvider()
    {
        yield 'Join two conditions' => [
            'query' => "modell:modell_string pn:1122334400_pn",
            'expected_results' => 1,
        ];
    }

    public function validMassUpdateObjektsProvider()
    {
        yield 'Move to container' => [
            'objects' => [[], [],],
            'query' => [
                'newstate' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT,
                'desc' => '(TEST) temporäre Verwahrung',
                'context' => 'DTHW12345',
            ]
        ];
        yield 'Zero drive' => [
            'objects' => [[
                'barcode' => 'DTHD00020',
                'kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
            ], [
                'barcode' => 'DTHD00024',
                'kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
            ],],
            'query' => [
                'newstate' => Objekt::STATUS_GENULLT,
                'desc' => '(TEST) Vorsichtshalber genullt, um Probleme zu vermeiden',
                ]
            ];
        yield 'Use multiple objekts' => [
            'objects' => [[], [],],
            'query' => [
                'newstate' => Objekt::STATUS_IN_VERWENDUNG,
                'desc' => '(TEST) Analyse einer Festplatte',
            ]
        ];
    }

    public function invalidMassUpdateObjektsProvider()
    {
        yield 'Zero exibits' => [
            'objects' => [[
                'barcode' => 'DTAS00020',
                'kategorie' => Objekt::KATEGORIE_ASSERVAT,
            ], [
                'barcode' => 'DTAS00024',
                'kategorie' => Objekt::KATEGORIE_AKTE,
            ],],
            'query' => [
                'newstate' => Objekt::STATUS_GENULLT,
                'desc' => '(TEST) Asservate sollten nicht genullt werden können',
            ], 
            'expected' => [
                'message' => 'object_is_not_a_hdd',
            ]
        ];
        yield 'Zero drives again' => [
            'objects' => [[
                'barcode' => 'DTHD00020',
                'kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
                'status' => Objekt::STATUS_GENULLT,
            ], [
                'barcode' => 'DTHD00024',
                'kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
                'status' => Objekt::STATUS_GENULLT,      
            ],],
            'query' => [
                'newstate' => Objekt::STATUS_GENULLT,
                'desc' => '(TEST) Festplatten müssen gekaputt genullt werden',
            ], 
            'expected' => [
                'message' => 'object_already_in_this_status',
            ]
        ];
        yield 'Non-existant object' => [
            'objects' => [[
                'barcode' => 'DTHD00020',
                'kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
                'status' => Objekt::STATUS_GENULLT,
            ], [
                'barcode' => 'DTHD00024',
                'kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
                'status' => Objekt::STATUS_GENULLT,
            ], [
                'volatile' => true,
                'barcode' => 'DTHD99999',
                'kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
                'status' => Objekt::STATUS_GENULLT,
            ],],
            'query' => [
                'newstate' => Objekt::STATUS_IN_VERWENDUNG,
                'desc' => '(TEST) DTHD99999 existiert natürlich',
            ], 
            'expected' => [
                'message' => 'objects.not.found',
            ]
        ];
    }

    //
    // ================ TESTS ================
    //

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

        // add object first time successful
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
        $prevState = $objekt->getStatus();

        // do request
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
        ]);
        // also test whether it was nulled
        $this->assertSelectorTextContains('#state', 'status.cleaned');
        $this->assertSelectorTextContains('#last_action_user', 'user');

        // test history
        $this->assertSelectorTextContains('#history', Objekt::getStatusNameFromId($prevState));
    }

    public function testNullObjektIncorrectObjekt()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->exhibit()->create();
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/nullen");
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
        ]);
        // also test whether it was nulled
        $this->assertSelectorTextNotContains('#state', 'status.cleaned');

        // test history
        $this->assertSelectorTextNotContains('#history', Objekt::getStatusNameFromId($prevState));
    }

    public function testDestroyObjekt()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->hdd()->create();
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/vernichtet");
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, ['form[verwendung]' => 'Aus Testzwecken wird dieses Objekt zerstört deklariert']);
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
        ]);
        // also test whether it was destroyed
        $this->assertSelectorTextContains('#state', 'status.destroyed');
        $this->assertSelectorTextContains('#last_action_user', 'user');

        // test history
        $this->assertSelectorTextContains('#history', Objekt::getStatusNameFromId($prevState));
    }

    public function testLostObjekt()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->hdd()->create();
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/verloren");
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, ['form[verwendung]' => 'Aus Testzwecken wird dieses Objekt verloren deklariert']);
        $this->assertResponseRedirects("/objekt/{$objekt->getBarcodeId()}");

        // test overview page
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $objekt->getBarcodeId(),
            'name' => $objekt->getName(),
        ]);

        // test details page
        $crawler = $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $objekt->getBarcodeId(),
            'name' => $objekt->getName(),
        ]);
        // also test whether it was lost
        $this->assertSelectorTextContains('#state', 'status.lost');
        $this->assertSelectorTextContains('#last_action_user', 'user');

        // test history
        $this->assertSelectorTextContains('#history', Objekt::getStatusNameFromId($prevState));
    }

    public function testChangeDestroyedObjekts()
    {
        // setup
        $factory = ObjektFactory::new();
        $objs = [
            $factory->hdd()->destroyed()->create(),
            $factory->hdd()->lost()->create(),
        ];

        $client = static::createClient();
        $client = $this->loginUser($client);

        foreach ($objs as $objekt) {   
            $client->request('POST', "/objekt/{$objekt->getBarcodeId()}/verwenden");
            $this->assertResponseRedirects("/objekt/{$objekt->getBarcodeId()}");
            $this->seeInDatabase(ObjektRepository::class, [
                'barcode_id' => $objekt->getBarcode(),
                'status_id' => $objekt->getStatus(),
            ]);
            
            $client->request('POST', "/objekt/{$objekt->getBarcodeId()}/editieren");
            $this->assertResponseRedirects("/objekt/{$objekt->getBarcodeId()}");
            $this->seeInDatabase(ObjektRepository::class, [
                'barcode_id' => $objekt->getBarcode(),
                'status_id' => $objekt->getStatus(),
            ]);
            
            $client->request('POST', "/objekt/{$objekt->getBarcodeId()}/einlegen/in");
            $this->assertResponseRedirects("/objekt/{$objekt->getBarcodeId()}");
            $this->seeInDatabase(ObjektRepository::class, [
                'barcode_id' => $objekt->getBarcode(),
                'status_id' => $objekt->getStatus(),
            ]);
            
            $storage = $factory->container()->create();        
            $client->request('POST', "/objekt/{$objekt->getBarcodeId()}/einlegen/in/{$storage->getBarcodeId()}");
            $this->assertResponseRedirects("/objekt/{$objekt->getBarcodeId()}");
            $this->seeInDatabase(ObjektRepository::class, [
                'barcode_id' => $objekt->getBarcode(),
                'status_id' => $objekt->getStatus(),
            ]);
        }
    }
    
    public function testReserveObjekt()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->hdd()->create();
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/reservieren");
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, ['form[verwendung]' => 'Es wird für einen Penetrationstests temporär reserviert']);
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
        ]);
        // also test whether it was reserved
        $this->assertSelectorTextContains('#state', 'status.reserved');
        $this->assertSelectorTextContains('#last_action_user', 'user');

        // test history
        $this->assertSelectorTextContains('#history', Objekt::getStatusNameFromId($prevState));
    }
    
    public function testStoreObjekt()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->hdd()->create();
        $storage = $factory->container()->create();
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/einlegen/in/{$storage->getBarcodeId()}");
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, ['form[verwendung]' => 'Aus Testzwecken wird dieses Objekt in den Schrank gelegt']);
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
        ]);
        // also test whether it is in storage
        $this->assertSelectorTextContains('#location', $storage->getBarcode());
        $this->assertSelectorTextContains('#state', 'status.stored.in.container');
        $this->assertSelectorTextContains('#last_action_user', 'user');

        // test history
        $this->assertSelectorTextContains('#history', Objekt::getStatusNameFromId($prevState));

        // test details page of storage
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $storage->getBarcodeId(),
            'name' => $storage->getName(),
        ]);
        // also test whether storage lists item
        $this->assertSelectorTextContains('#container_info', $objekt->getBarcode());
    }

    /**
     * @depends testStoreObjekt
     */
    public function testStoreObjektInvalidSelfStore()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->container()->create();
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/einlegen/in/{$objekt->getBarcodeId()}");
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
        ]);
        // test object not altered
        $this->assertSelectorTextContains('#state', Objekt::getStatusNameFromId($prevState));

        // also test whether object is not self stored
        $this->assertSelectorTextNotContains('#location', $objekt->getBarcode());
        // also test whether storage doesnt list item
        $this->assertSelectorTextNotContains('#container_info', $objekt->getBarcode());
        // test history
        $this->assertSelectorTextNotContains('#history', Objekt::getStatusNameFromId($prevState));
    }

    /**
     * @todo improve test
     * @depends testStoreObjektInvalidSelfStore
     */
    public function testStoreObjektInvalidStoreAgain()
    {
        // setup
        $factory = ObjektFactory::new();
        $storage = $factory->container()->create();
        // create object which is in storage
        $objekt = $factory->hdd()->create(['standort' => $storage, 'status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT]);
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/einlegen/in/{$storage->getBarcodeId()}");
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
        ]);
        $this->assertSelectorTextContains('#location', $storage->getBarcode());
        $this->assertSelectorTextContains('#state', Objekt::getStatusNameFromId($prevState));
        $this->assertSelectorTextNotContains('#last_action_user', 'user'); // no action was recorded

        $this->assertSelectorTextContains('#state', 'status.stored.in.container');
        
        // test history
        $this->assertSelectorTextNotContains('#history', Objekt::getStatusNameFromId($prevState));

        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $storage->getBarcodeId(),
            'name' => $storage->getName(),
        ]);
        // also test whether storage lists item
        $this->assertSelectorTextContains('#container_info', $objekt->getBarcode());
    }

    /**
     * @depends testStoreObjektInvalidStoreAgain
     */
    public function testStoreObjektInvalidCyclicStore()
    {
        // setup
        $factory = ObjektFactory::new();
        $container1 = $factory->container()->create();
        // container2 stored in container1
        $container2 = $factory->container()->create(['standort' => $container1, 'status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT]);

        $client = static::createClient();
        $this->loginUser($client)->request('POST', "/objekt/{$container1->getBarcodeId()}/einlegen/in/{$container2->getBarcodeId()}");
        $this->assertResponseRedirects("/objekt/{$container1->getBarcodeId()}");

        // test overview page
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $container1->getBarcodeId(),
            'name' => $container1->getName(),
        ]);
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $container2->getBarcodeId(),
            'name' => $container2->getName(),
        ]);

        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $container1->getBarcodeId(),
            'name' => $container1->getName(),
        ]);
        // also test whether error message is shown
        $this->assertSelectorTextNotContains('#location', $container2->getBarcode());
        $this->assertSelectorTextNotContains('#last_action_user', 'user'); // no action was recorded
        $this->assertSelectorTextContains('#container_info', $container2->getBarcode());
        // test history
        $this->assertSelectorTextNotContains('#history', 'status.stored.in.container');
    

        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $container2->getBarcodeId(),
            'name' => $container2->getName(),
        ]);
        // also test whether storage lists item
        $this->assertSelectorTextContains('#location', $container1->getBarcode());
        $this->assertSelectorTextNotContains('#last_action_user', 'user'); // no action was recorded
        $this->assertSelectorTextNotContains('#container_info', $container1->getBarcode());
    }

    /**
     * @depends testStoreObjektInvalidCyclicStore
     */
    public function testStoreObjektInvalidCyclicStoreThreeItems()
    {
        // setup
        $factory = ObjektFactory::new();
        $container1 = $factory->container()->create();
        // container2 stored in container1
        $container2 = $factory->container()->create(['standort' => $container1, 'status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT]);
        // container3 stored in container2
        $container3 = $factory->container()->create(['standort' => $container2, 'status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT]);

        $client = static::createClient();
        $this->loginUser($client)->request('POST', "/objekt/{$container1->getBarcodeId()}/einlegen/in/{$container3->getBarcodeId()}");
        $this->assertResponseRedirects("/objekt/{$container1->getBarcodeId()}");

        // test overview page
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $container1->getBarcodeId(),
            'name' => $container1->getName(),
        ]);
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $container2->getBarcodeId(),
            'name' => $container2->getName(),
        ]);
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $container3->getBarcodeId(),
            'name' => $container3->getName(),
        ]);

        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $container1->getBarcodeId(),
            'name' => $container1->getName(),
        ]);
        // also test whether error message is shown
        $this->assertSelectorTextNotContains('#location', $container3->getBarcode());
        $this->assertSelectorTextNotContains('#last_action_user', 'user'); // no action was recorded
        $this->assertSelectorTextContains('#container_info', $container2->getBarcode());
        // test history
        $this->assertSelectorTextNotContains('#history', 'status.stored.in.container');

        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $container2->getBarcodeId(),
            'name' => $container2->getName(),
        ]);
        // also test whether storage lists item
        $this->assertSelectorTextContains('#currentstatus', $container1->getBarcode());
        $this->assertSelectorTextContains('#additionalinformation', $container3->getBarcode());
        $this->assertSelectorTextNotContains('#currentstatus', $container3->getBarcode());
        $this->assertSelectorTextNotContains('#additionalinformation', $container1->getBarcode());
        
        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $container3->getBarcodeId(),
            'name' => $container3->getName(),
        ]);
        // also test whether storage lists item
        $this->assertSelectorTextContains('#currentstatus', $container2->getBarcode());
        $this->assertSelectorTextNotContains('#currentstatus', $container1->getBarcode());
        $this->assertSelectorExists('#additionalinformation');
        $this->assertSelectorTextNOTContains('#additionalinformation', $container1->getBarcode());
        $this->assertSelectorTextNotContains('#additionalinformation', $container2->getBarcode());
    }
    
    /**
     * @depends testStoreObjekt
     */
    public function testStoreObjektInvalidDestroyedContainer()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->hdd()->create();
        $storage = $factory->container()->destroyed()->create();
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/einlegen/in/{$storage->getBarcodeId()}");
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
        ]);
        // also test whether it is in storage
        $this->assertSelectorTextNotContains('#location', $storage->getBarcode());
        $this->assertSelectorTextContains('#state', Objekt::getStatusNameFromId($prevState));
        $this->assertSelectorTextNotContains('#last_action_user', 'user'); // no action was recorded
        // test history
        $this->assertSelectorTextNotContains('#history', Objekt::getStatusNameFromId($prevState));
    }
    
    /**
     * @depends testStoreObjekt
     */
    public function testStoreObjektInvalidContainer()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->hdd()->create();
        $storage = $factory->container()->create();
        $prevState = $storage->getStatus();

        // do request
        $client = static::createClient();
        // reverse order
        $this->loginUser($client)->request('POST', "/objekt/{$storage->getBarcodeId()}/einlegen/in/{$objekt->getBarcodeId()}");
        $this->assertResponseRedirects("/objekt/{$storage->getBarcodeId()}");

        // test overview page
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $objekt->getBarcodeId(),
            'name' => $objekt->getName(),
        ]);
        $this->assertInOverviewPageCorrect($client, [
            'barcode_id' => $storage->getBarcodeId(),
            'name' => $storage->getName(),
        ]);

        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $storage->getBarcodeId(),
            'name' => $storage->getName(),
        ]);
        // also test whether it is in storage
        $this->assertSelectorTextNotContains('#location', $storage->getBarcode());
        $this->assertSelectorTextContains('#state', Objekt::getStatusNameFromId($prevState));
        $this->assertSelectorTextNotContains('#last_action_user', 'user'); // no action was recorded
        // test history
        $this->assertSelectorTextNotContains('#history', Objekt::getStatusNameFromId($prevState));
    }

    public function testAssignToCaseObjekt()
    {
        // setup
        $factory = ObjektFactory::new();
        $objekt = $factory->create();
        $caseFactory = FallFactory::new();
        $case = $caseFactory->create();
        $prevState = $objekt->getStatus();

        // do request
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/in/fall/{$case->getCaseId()}/hinzufuegen");
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, ['form[verwendung]' => 'Musste beim Fall hinzugezogen werden']);
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
        ]);
        // also test whether it was added to case
        $this->assertSelectorTextContains('#state', 'status.added.to.case');
        $this->assertSelectorTextContains('#case', $case->getCaseId());
        $this->assertSelectorTextContains('#last_action_user', 'user');
        // test history
        $this->assertSelectorTextContains('#history', Objekt::getStatusNameFromId($prevState));
    }

    /**
     * @dataProvider simpleSearchParamsProvider
     * @dataProvider complexSearchParamsProvider
     */
    public function testSearchObjekts($query, $expectedResults)
    {
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();
        $userFactory = \App\Tests\Factory\NutzerFactory::new();
        $caseFactory = FallFactory::new();

        $samples = [
            [
                'Barcode' => ObjektFactory::generateBarcode('DTAS'),
                'Kategorie' => Objekt::KATEGORIE_ASSERVAT,
                'Status' => Objekt::STATUS_EINGETRAGEN,
                'Name' => 'name_string',
                'Notiz' => 'note_string',
                'Zeitstempel' => date_create_from_format('d.m.Y', "24.02.2042"),
            ],
            [
                'Barcode' => 'DTHD12345',
                'Kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
                'Status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT,
                'Verwendung' => 'current_description',
                'Standort' => $factory->create([
                    'Barcode' => 'DTHW33344',
                    'Kategorie' => Objekt::KATEGORIE_BEHAELTER,
                    'Status' => Objekt::STATUS_EINGETRAGEN,
                ]),
                'Zeitstempel' => date_create_from_format('d.m.Y', "01.01.1970"),
                'hdd' => [
                    'bauart' => 'extern', 
                    'formfaktor' => '2,5', 
                    'groesse' => '666', 
                    'anschluss' => 'USB', 
                    'hersteller' => 'prod_string',
                    'SN' => '9988776655_sn',
                ]
            ],
            [
                'Barcode' => ObjektFactory::generateBarcode('DTHD'),
                'Kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
                'Status' => Objekt::STATUS_GENULLT,
                'Verwendung' => 'current_description',
                'Fall' => $caseFactory->create([
                    'case_id' => 'case_id',
                    'istAktiv' => true,
                ]),
                'Zeitstempel' => date_create_from_format('d.m.Y', "25.05.2255"),
                'hdd' => [
                    'bauart' => 'extern', 
                    'formfaktor' => '3,5', 
                    'groesse' => '128', 
                    'anschluss' => 'SATA', 
                    'modell' => 'modell_string',
                    'PN' => '1122334400_pn',
                ]
            ],
            [
                'Barcode' => ObjektFactory::generateBarcode('DTHW'),
                'Kategorie' => Objekt::KATEGORIE_AUSRUESTUNG,
                'Status' => Objekt::STATUS_RESERVIERT,
                'ReserviertVon' => $userFactory->create([
                    'username' => 'current_reservation',
                    'fullname' => 'current_reservation'
                ]),
            ],
        ];

        // setup
        $factory->disableAutomaticDriveGeneration();
        foreach ($samples as $entry) {
            $hdd = $entry['hdd'] ?? false;
            unset($entry['hdd']);

            $obj = $factory->create($entry);
            // create drive if required
            if($hdd){
                $hdd['barcode'] = $obj->getBarcode();
                $driveFactory->create($hdd);
            }
        }
        $factory->enableAutomaticDriveGeneration();


        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('POST', "/objekte");
        $form = $crawler->filter("#search_form")->form();
        $crawler = $client->submit($form, [
            'form[search]' => $query,
            'form[limit]' => '1000'
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertEquals($expectedResults, $crawler->filter("tbody tr")->count(), "Unexpected number of search results"); 
    }

    /**
     * @dataProvider validMassUpdateObjektsProvider
     */
    public function testMassUpdateObjekts($objekts, $query)
    {
        // setup
        $factory = ObjektFactory::new();
        $factory->container()->create(['Barcode' => 'DTHW12345']);

        // create objecs
        foreach ($objekts as $key => $value) {
            $objekts[$key] = $factory->create($objekts[$key]);
        }


        $client = static::createClient();
        $crawler = $this->loginUser($client)->request("POST",
            '/objekte/aendern/', 
            isset($query['context']) ? [
                'action_choose' => [
                    'searchbox' => $query['context'],
                    'newstatus' => $query['newstate']
                ]
            ]: [],
            [], 
            ['HTTP_X-Requested-With' => 'XMLHttpRequest'],
        );
        $form = $crawler->selectButton('Select objects')->form();
        // dueDate has to be set manually, cause of the indirect submit of the page
        $crawler = $client->submit($form, isset($query['context']) ? [
                'action_choose[newdescription]' => $query['desc'],
                'action_choose[newstatus]' => $query['newstate'],
                'action_choose[contextthings]' => $query['context'],
                'action_choose[dueDate]' => '2018-10-12T10:00:00'
            ] : [
                'action_choose[newdescription]' => $query['desc'],
                'action_choose[newstatus]' => $query['newstate']   
            ]
        );

        $this->assertResponseRedirects('/objekte/aendern/in');

        // follow redirect and set values
        $form = $client->followRedirect()->selectButton('label.do.action')->form();
        $formdata = $form->getPhpValues();
        
        // add objects to be altered
        foreach($objekts as $key => $obj){
            $formdata['form']['objects'][$key] = $obj->getBarcode();
            
        }

        // make request
        $crawler = $client->request($form->getMethod(), 
        $form->getUri(), $formdata, $form->getPhpFiles());
        $this->assertResponseRedirects('/objekte');

        // verify changes on objects
        foreach ($objekts as $obj) {
            $this->seeInDatabase(ObjektRepository::class, [
                'barcode_id' => $obj->getBarcode(),
                'status_id' => $query['newstate'],
            ]);
        }
    }

    /**
     * @dataProvider invalidMassUpdateObjektsProvider
     */
    public function testMassUpdateObjektsInvalidChanges($config, $query, $expected)
    {
        // setup
        $factory = ObjektFactory::new();
        $factory->container()->create(['Barcode' => 'DTHW12345']);
        $objekts = [];

        // create objecs
        foreach ($config as $key => $value) {
            if (!empty($value['volatile'])) {
                unset($value['volatile']);
                $objekts[$key] = $factory->withoutPersisting()->create($value);
                $value['volatile'] = true;
            }
            else {
                $objekts[$key] = $factory->create($value);
            }
        }

        $client = static::createClient();
        $crawler = $this->loginUser($client)->request("POST",
            '/objekte/aendern/', 
            isset($query['context']) ? [
                'action_choose' => [
                    'searchbox' => $query['context'],
                    'newstatus' => $query['newstate']
                ]
            ]: [],
            [], 
            ['HTTP_X-Requested-With' => 'XMLHttpRequest'],
        );
        $form = $crawler->selectButton('Select objects')->form();
        // dueDate has to be set manually, cause of the indirect submit of the page
        $crawler = $client->submit($form, isset($query['context']) ? [
                'action_choose[newdescription]' => $query['desc'],
                'action_choose[newstatus]' => $query['newstate'],
                'action_choose[contextthings]' => $query['context'],
                'action_choose[dueDate]' => '2018-10-12T10:00:00'
            ] : [
                'action_choose[newdescription]' => $query['desc'],
                'action_choose[newstatus]' => $query['newstate']   
            ]
        );

        $this->assertResponseRedirects('/objekte/aendern/in');

        // follow redirect and set values
        $form = $client->followRedirect()->selectButton('label.do.action')->form();
        $formdata = $form->getPhpValues();
        
        // add objects to be altered
        foreach($objekts as $key => $obj){
            $formdata['form']['objects'][$key] = $obj->getBarcode();
            
        }

        // make request
        $client->request($form->getMethod(), $form->getUri(), $formdata, $form->getPhpFiles());
        $this->assertSelectorTextContains('div.alert.alert-danger', $expected['message']);

        // verify no changes on objects
        foreach ($config as $key => $value) {
            if(isset($value['volatile'])){
                continue;
            }
            $this->seeInDatabase(ObjektRepository::class, [
                'barcode_id' => $objekts[$key]->getBarcode(),
                'status_id' => $objekts[$key]->getStatus(),
            ]);
        }
    }


    public function testAddImageToHDD()
    {
        // setup
        $factory = ObjektFactory::new();
        $exhibitHdd = $factory->exhibitHdd()->create();
        $objekt = $factory->hdd()->create();

        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('POST', "/objekt/{$objekt->getBarcodeId()}/Asservatenimage/speichern/von/{$exhibitHdd->getBarcodeId()}/0");
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, ['form[verwendung]' => 'Eine Bitweise Kopie erstellt. Beim Kopieren wurden jedoch fehlerhafte Sektoren übersprungen']);
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
        ]);
        $this->assertSelectorTextContains('#images', $exhibitHdd->getBarcode());
        $this->assertSelectorTextContains('#images_info', $exhibitHdd->getBarcode());

        // test details page
        $this->assertDetailsPageCorrect($client, [
            'barcode_id' => $exhibitHdd->getBarcodeId(),
            'name' => $exhibitHdd->getName(),
        ]);
        $this->assertSelectorTextContains('#image_locations', $objekt->getBarcode());
        $this->assertSelectorTextContains('#image_locations_info', $objekt->getBarcode());
    }



    // TODO: Tests der Historieneintraege nach der editierung eines Objekts
    // EDIT reset form ansehen
    
    // TODO: Tests zum Upload von Bildern hinzufuegen
    // TODO: Tests zum Word-Export hinzufuegen
    // TODO: Faelle mit Objekten pruefen

    //
    // ================ HELPER METHODS ================
    //

    protected function assertInOverviewPageCorrect($client, $objekt): Crawler
    {
        $crawler = $client->request('GET', '/objekte');
        $this->assertSelectorTextContains("tr:contains('{$objekt['barcode_id']}')", $objekt['barcode_id']);
        $this->assertSelectorTextContains("tr:contains('{$objekt['barcode_id']}')", $objekt['name']);

        return $crawler;
    }

    protected function assertDetailsPageCorrect($client, $objekt, $datentraeger = null): Crawler
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
