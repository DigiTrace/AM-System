<?php

namespace App\Tests\Service;

use App\Entity\Objekt;
use App\Service\ExtendedAssetSearch;
use App\Tests\Factory\DatentraegerFactory;
use App\Tests\Factory\FallFactory;
use App\Tests\Factory\NutzerFactory;
use App\Tests\Factory\ObjektFactory;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use ReflectionClass;
use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSameSize;

class ExtendedAssetSearchTest extends KernelTestCase
{

    // helper function
    private function getInstance(): ExtendedAssetSearch
    {
        // (1) boot the Symfony kernel
        self::bootKernel();
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        return $container->get(ExtendedAssetSearch::class);
    }
    
    /**
     * Helper function to assert that two sets have the same objects in relation to $method attribute.
     * 
     * @param array  $expected Set of expected objects
     * @param array  $result   Set of result objects
     * @param string $method   Method to call
     * @return void
     */
    private function assertAttributeInSet(array $expected, array $result, string $method){
        assertSameSize($expected, $result);

        $set = [];
        // unpack expected value set
        foreach ($expected as $element) {
            $set[$element->getBarcode()] = $element->$method();
        }

        foreach ($result as $element) {
            // test all expected items to find match
            $barcode = $element->getBarcode();
            assertArrayHasKey($barcode, $set, "Object $barcode was not expected to be found.");
            $value = $element->$method();
            // test primitive or object
            if (is_object($value)){
                // test for user objetcs
                if(method_exists($set[$barcode], 'getId')){
                    assertEquals($set[$barcode]->getId(), $value->getId(), "Asserting $method objects have same ids has failed for object $barcode.");
                }
                // ... or regular objects
                else {
                    assertEquals($set[$barcode]->getBarcode(), $value->getBarcode(), "Asserting $method objects have same barcodes has failed for object $barcode.");
                }
            }
            else {
                assertEquals($set[$barcode], $value, "Asserting $method property is equal has failed for object $barcode.");
            }
            // remove found element from result set
            unset($set[$barcode]);
        }

        assertEmpty($set);
    }

    /**
     * Test query with extended asset search and verify in object to $method
     * @param \App\Service\ExtendedAssetSearch $search
     * @param array $queries
     * @param array $expected
     * @param string $method
     * @return void
     */
    private function testQuery(ExtendedAssetSearch $search, array $queries, array $expected, string $method){
        foreach ($queries as $q) {
            $res = $search->generateSearchQuery($q)->execute();

            if(empty($expected)){
                assertEmpty($res, "query '$q' did not return empty result");
            }
            else {
                $this->assertAttributeInSet($expected, $res, $method);
            }
        }
    }

    public function matchProvider(){
        // [query, #matches single, #matches mult]
        yield ['test', 0, 0];
        yield ['name:heinz', 1, 0];
        yield ['name:(heinz)', 1, 0];
        yield ['name:[heinz]', 0, 1];
        yield ['name:heinz name:franz cat:[0|1]', 2, 1];
        yield ['name:heinz s:[1|2] cat:[0|1]', 1, 2];
        yield ['c:"a <> äüp" c:\'a <> äüp\' c:öüäp', 3, 0];
        yield ['c:"a <> äüp\' c:"a <> äüp\' c:\'äüöp', 0, 0];

        yield ['c:[Asservat|Datentraeger] name:"HDD" || c:2 s:2', 3, 1];
    }

    /**
     * @dataProvider matchProvider
     */
    public function testMatchValue($query, $single, $mult){

        $obj = $this->getInstance();

        $method = (new ReflectionClass(ExtendedAssetSearch::class))->getMethod('matchKeySingleValue');
        $method->setAccessible(true);
        $res = $method->invokeArgs($obj, [$query]);
        assertCount($single, $res);

        $method = (new ReflectionClass(ExtendedAssetSearch::class))->getMethod('matchKeyMultipleValue');
        $method->setAccessible(true);
        $res = $method->invokeArgs($obj, [$query]);
        assertCount($mult, $res);
    }

    public function keyValueProvider() {
        // [query, single key-val pairs, mult key-val pairs]
        yield [
            '!s:1 c:0 name:"Heinz " barcode:\'DTHW32310\' c:[0|1] name:["Franz F."|\'Günther D.\'| possible]', 
            [
                '!s' => '1',
                'c' => '0',
                'name' => '"Heinz "',
                'barcode' => '\'DTHW32310\'',
            ], [
                'c' => '0|1',
                'name' => '"Franz F."|\'Günther D.\'| possible'
        ]];
        yield [
            'd:>20.01.2001 mdate:[31.03.2023|30.03.2023]', 
            [
                'd' => '>20.01.2001',
            ], [
                'mdate' => '31.03.2023|30.03.2023',
        ]];
    }

    /**
     * @dataProvider keyValueProvider
     */
    public function testMatchKeyValue($query, $single, $mult){
        $obj = $this->getInstance();

        $method = (new ReflectionClass(ExtendedAssetSearch::class))->getMethod('matchKeySingleValue');
        $method->setAccessible(true);
        $res = $method->invokeArgs($obj, [$query]);
        $i = 0;
        foreach ($single as $key => $value){
            assertEquals($res[$i][1], $key);
            assertEquals($res[$i][2], $value);
            ++$i;
        }

        $method = (new ReflectionClass(ExtendedAssetSearch::class))->getMethod('matchKeyMultipleValue');
        $method->setAccessible(true);
        $res = $method->invokeArgs($obj, [$query]);
        $i = 0;
        foreach ($mult as $key => $value){
            assertEquals($res[$i][1], $key);
            assertEquals($res[$i][2], $value);
            ++$i;
        }
    }

    public function queryValueProvider() {
        // [query, parsed values]
        yield [
            '!s:1 c:0 name:"Heinz" barcode:\'DTHW32310\' !c:[0|1] name:["Franz F."|\'Günther D.\'|possible]', 
            [
                ['neg' => true, 'key' => 's', 'val' => ['1']],
                ['neg' => false, 'key' => 'c', 'val' => ['0']],
                ['neg' => false, 'key' => 'name', 'val' => ['Heinz']],
                ['neg' => false, 'key' => 'barcode', 'val' => ['DTHW32310']],
                ['neg' => true, 'key' => 'c', 'val' => [0,1]],
                ['neg' => false, 'key' => 'name', 'val' => ['Franz F.', 'Günther D.', 'possible']],
            ]
        ];
        yield [
            'd:>20.01.2001 mdate:[31.03.2023|30.03.2023]', 
            [
                ['neg' => false, 'key' => 'd', 'val' => ['>20.01.2001']],
                ['neg' => false, 'key' => 'mdate', 'val' => ['31.03.2023', '30.03.2023']],
            ],
        ];
    }

    /**
     * @dataProvider queryValueProvider
     */
    public function testGetQueryValues($query, $values) {
        $obj = $this->getInstance();
        $method = (new ReflectionClass(ExtendedAssetSearch::class))->getMethod('getQueryValues');
        $method->setAccessible(true);
        $res = $method->invokeArgs($obj, [$query]);
        assertEquals($values, $res);
    }

    public function extendedSearchCheckProvider() {
        // [query, isExtended]
        yield ['suche', false];
        yield ['komplizierter suchterm', false];
        yield ['c:1', true];
        yield ['dasist:einekomplexesuche', true];
    }

    /**
     * @dataProvider extendedSearchCheckProvider
     */
    public function testIsExtended($query, $isExtended){
        $search = $this->getInstance();
        assertEquals($isExtended, $search->isExtendedQuery($query));
    }

    //
    // ========= TEST QUERY METHODS =========
    //

    public function testCategoryQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        // create assets with category 1 and not 1
        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001']),
            $factory->exhibit()->create(['barcode' => 'DTAS00002']),
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
            $factory->container()->create(['barcode' => 'DTHW00003']),
            $factory->container()->create(['barcode' => 'DTHW00004']),
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->record()->create(['barcode' => 'DTAS00003']),
            $factory->record()->create(['barcode' => 'DTAS00004']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006']),
        ];
        
        // test single
        $this->testQuery($search, [
            'c:1', 'k:1', 'cat:1', 'kat:1', 'category:1', 'kategorie:1',
            '!c:[0|2|3|4|5]', '!k:[0|2|3|4|5]', '!cat:[0|2|3|4|5]', '!kat:[0|2|3|4|5]', '!category:[0|2|3|4|5]', '!kategorie:[0|2|3|4|5]',
        ], [$samples[3], $samples[2]], 'getKategorie');

        // test multiple 
        $this->testQuery($search, [
            'c:[2|3]', 'c:2 || c:3'
        ], [$samples[4], $samples[5], $samples[6], $samples[7]], 'getKategorie');
    }

    public function testStateQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        // create assets with state 4 and 0
        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'status' => Objekt::STATUS_EINGETRAGEN]),                // 0
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'status' => Objekt::STATUS_EINGETRAGEN]),                // 1
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'status' => Objekt::STATUS_AN_PERSON_UEBERGEBEN]),    // 2
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'status' => Objekt::STATUS_AN_PERSON_UEBERGEBEN]),    // 3
        ];
        
        $this->testQuery($search, [
            's:4', 'status:4',
            '!s:[0|1|2|3|5|6|7|8|9|10|11|12|13|14]',
        ], [$samples[2], $samples[3]], 'getStatus');
    }

    public function testBarcodeQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001']),        // 0
            $factory->exhibit()->create(['barcode' => 'DTAS00002']),        // 1
            $factory->equipment()->create(['barcode' => 'DTHW00001']),      // 2
            $factory->equipment()->create(['barcode' => 'DTHW00002']),      // 3
            $factory->container()->create(['barcode' => 'DTHW00003']),      // 4
            $factory->container()->create(['barcode' => 'DTHW00004']),      // 5
            $factory->hdd()->create(['barcode' => 'DTHD00001']),            // 6
            $factory->hdd()->create(['barcode' => 'DTHD00002']),            // 7
            $factory->record()->create(['barcode' => 'DTAS00003']),         // 8
            $factory->record()->create(['barcode' => 'DTAS00004']),         // 9
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005']),     // A
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006']),     // B
        ];
        
        $this->testQuery($search, [
            'barcode:DTHD', 'b:[DTHD00001|"DTHD00002"]'
        ], [$samples[6], $samples[7]], 'getBarcode');
        
        $this->testQuery($search, ['b:"4"'], [$samples[5], $samples[9]], 'getBarcode');
        
        // empty results
        $this->testQuery($search, [
            'barcode:40', "!b:D"
        ], [], 'getBarcode');

    }

    public function testNameQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'name' => 'Albert']),    // 0
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'name' => 'Bert']),      // 1
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'name' => 'Carlos']), // 2
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'name' => 'Dustin']), // 3
        ];
        
        $this->testQuery($search, [
            'n:Albert', 'name:"al"'
        ], [$samples[0]], 'getName');
        
        $this->testQuery($search, [
            '!n:bert', 'name:["Carlos"|"dustin"]'
        ], [$samples[2], $samples[3]], 'getName');
    }

    public function testDescriptionQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'verwendung' => 'Dies ist ein text mit leerzeichen']),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'verwendung' => 'Komische Zahlen 23123!']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'verwendung' => '']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'verwendung' => 'xyz']),
        ];
        
        $this->testQuery($search, [
            'desc:xyz', 'description:"xyz"'
        ], [$samples[3]], 'getVerwendung');

        $this->testQuery($search, [
            'desc:"Dies ist ein text mit leerzeichen"'
        ], [$samples[0]], 'getVerwendung');

        $this->testQuery($search, [
            'desc:"123!"'
        ], [$samples[1]], 'getVerwendung');

        $this->testQuery($search, [
            '!desc:" "'
        ], [$samples[2], $samples[3]], 'getVerwendung');
    }

    public function testFormerDescriptionQuery() {
        $this->markTestIncomplete();
    }

    public function testUserQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        $userFactory = NutzerFactory::new();
        $alice = $userFactory->enabled()->testPassword()->with([
            'username' => 'alice',
            'fullname' => 'alice',
            'email' => 'alice@localhost',
        ])->create()->_real();
        $bob = $userFactory->enabled()->testPassword()->with([
            'username' => 'bob',
            'fullname' => 'bob',
            'email' => 'bob@localhost',
        ])->create()->_real();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'nutzer' => $alice]),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'nutzer' => $alice]),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'nutzer' => $bob]),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'nutzer' => $bob]),
        ];
        
        $this->testQuery($search, [
            'u:alice', '!u:bob'
        ], [$samples[0], $samples[1]], 'getNutzer');
        
        $this->testQuery($search, [
            '!u:alice', 'u:bob'
        ], [$samples[2], $samples[3]], 'getNutzer');
    }

    public function testFormerUserQuery() {
        $this->markTestIncomplete();
    }

    public function testReservedQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        $userFactory = NutzerFactory::new();
        $alice = $userFactory->enabled()->testPassword()->with([
            'username' => 'alice',
            'fullname' => 'alice',
            'email' => 'alice@localhost',
        ])->create()->_real();
        $bob = $userFactory->enabled()->testPassword()->with([
            'username' => 'bob',
            'fullname' => 'bob',
            'email' => 'bob@localhost',
        ])->create()->_real();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'status' => Objekt::STATUS_RESERVIERT, 'reserviert_von' => $alice]),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'status' => Objekt::STATUS_RESERVIERT, 'reserviert_von' => $bob]),
            $factory->equipment()->create(['barcode' => 'DTHW00001', 'status' => Objekt::STATUS_RESERVIERUNG_AUFGEHOBEN]),
            $factory->equipment()->create(['barcode' => 'DTHW00002', 'status' => Objekt::STATUS_RESERVIERUNG_AUFGEHOBEN]),
            $factory->container()->create(['barcode' => 'DTHW00003']),
            $factory->container()->create(['barcode' => 'DTHW00004']),
        ];
        
        $this->testQuery($search, [
            'r:t'
        ], [$samples[0], $samples[1]], 'getreserviertVon');

        $this->testQuery($search, [
            'r:f'
        ], [$samples[2], $samples[3], $samples[4], $samples[5]], 'getreserviertVon');
        
        $this->testQuery($search, [
            'r:alice',
        ], [$samples[0]], 'getreserviertVon');
        
        $this->testQuery($search, [
            'r:bob',
        ], [$samples[1]], 'getreserviertVon');
        
        // empty results
        $this->testQuery($search, [
            'r:andreas',
        ], [], 'getreserviertVon');
    }

    public function testFormerReservedQuery() {
        $this->markTestIncomplete();
    }

    public function testLocationQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        $container = [
            $factory->container()->create(['barcode' => 'DTHW00003']),
            $factory->container()->create(['barcode' => 'DTHW00004']),
        ];
        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001', 'status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT, 'standort' => $container[0]]),
            $factory->hdd()->create(['barcode' => 'DTHD00002', 'status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT, 'standort' => $container[0]]),
            $factory->record()->create(['barcode' => 'DTAS00003', 'status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT, 'standort' => $container[1]]),
            $factory->record()->create(['barcode' => 'DTAS00004', 'status' => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT, 'standort' => $container[1]]),
        ];
        
        // test all
        $this->testQuery($search, [
            'l:t', 'location:t', 'l:[DTHW00003|DTHW00004]', 'l:DTHW'
        ], $samples, 'getStandort');
        
        // test container
        $this->testQuery($search, [
            'l:f', 
        ], $container, 'getStandort');
        
        // test single
        $this->testQuery($search, [
            'l:"DTHW00003"'
        ], [$samples[0], $samples[1]], 'getStandort');
    }
    
    public function testFormerLocationQuery() {
        $this->markTestIncomplete();
    }

    public function testCaseQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $caseFactory = FallFactory::new();

        $cases = [
            $caseFactory->active()->create(['case_id' => 'Fall 1']),
            $caseFactory->active()->create(['case_id' => 'Fall 2']),
        ];

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001', 'status' => Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT, 'fall' => $cases[0]]),
            $factory->hdd()->create(['barcode' => 'DTHD00002', 'status' => Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT, 'fall' => $cases[1]]),
            $factory->record()->create(['barcode' => 'DTAS00003']),
            $factory->record()->create(['barcode' => 'DTAS00004']),
        ];
        
        // test set
        $this->testQuery($search, [
            'case:t', 'case:["Fall 1"|"Fall 2"]', 'case:Fall'
        ], [$samples[0], $samples[1]], 'getFall');
        
        // test not set
        $this->testQuery($search, [
            'case:f' 
        ], [$samples[2], $samples[3]], 'getFall');
        
        // test single
        $this->testQuery($search, [
            'case:"Fall 1"', '!case:"Fall 2"'
        ], [$samples[0]], 'getFall');
    }
    
    public function testFormerCaseQuery() {
        $this->markTestIncomplete();
    }

    public function testCaseActiveQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $caseFactory = FallFactory::new();

        $cases = [
            $caseFactory->active()->create(['case_id' => 'Aktiv 1']),
            $caseFactory->inactive()->create(['case_id' => 'Aktiv 2']),
        ];

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001', 'status' => Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT, 'fall' => $cases[0]]),
            $factory->hdd()->create(['barcode' => 'DTHD00002', 'status' => Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT, 'fall' => $cases[1]]),
            $factory->record()->create(['barcode' => 'DTAS00003']),
            $factory->record()->create(['barcode' => 'DTAS00004']),
        ];
        
        // test active
        $this->testQuery($search, [
            'caseactive:t'
        ], [$samples[0]], 'getFall');
        
        // test not set
        $this->testQuery($search, [
            'caseactive:f' 
        ], [$samples[1]], 'getFall');
    }

    public function testNoteQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'notiz' => 'Dies ist ein text mit leerzeichen']),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'notiz' => 'Komische Zahlen 23123!']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'notiz' => '']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'notiz' => 'xyz']),
        ];
        
        $this->testQuery($search, [
            'info:xyz', 'note:"xyz"'
        ], [$samples[3]], 'getNotiz');

        $this->testQuery($search, [
            'note:"Dies ist ein text mit leerzeichen"'
        ], [$samples[0]], 'getNotiz');

        $this->testQuery($search, [
            'note:"123!"'
        ], [$samples[1]], 'getNotiz');

        $this->testQuery($search, [
            '!note:" "'
        ], [$samples[2], $samples[3]], 'getNotiz');
    }

    public function testDateQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();

        // create assets with category 1 and not 1
        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2010-03-14')]),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2011-03-14')]),
            $factory->equipment()->create(['barcode' => 'DTHW00001', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2012-03-14')]),
            $factory->equipment()->create(['barcode' => 'DTHW00002', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2013-03-14')]),
            $factory->container()->create(['barcode' => 'DTHW00003', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2014-03-14')]),
            $factory->container()->create(['barcode' => 'DTHW00004', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2015-03-14')]),
            $factory->hdd()->create(['barcode' => 'DTHD00001', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2016-03-14')]),
            $factory->hdd()->create(['barcode' => 'DTHD00002', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2017-03-14')]),
            $factory->record()->create(['barcode' => 'DTAS00003', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2018-03-14')]),
            $factory->record()->create(['barcode' => 'DTAS00004', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2019-03-14')]),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2020-03-14')]),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'zeitstempel' => DateTime::createFromFormat('Y-m-d', '2021-03-14')]),
        ];
        
        // test simple single date queries
        foreach ([
            'd:14.03.2012', 'mdate:14.03.12', '!d:[<14.03.2012|>14.03.2012]'
        ] as $q) {
            $res = $search->generateSearchQuery($q)->execute();
            assertCount(1, $res);
            assertEquals($samples[2]->getBarcode(), $res[0]->getBarcode());
            assertEquals($samples[2]->getZeitstempel(), $res[0]->getZeitstempel());
        }

        // test simple date range queries
        foreach ([
            'd:>13.03.2021', 'mdate:>13.03.2021', '!d:<14.03.2021'
        ] as $q) {
            $res = $search->generateSearchQuery($q)->execute();
            assertCount(1, $res);
            assertEquals($samples[11]->getBarcode(), $res[0]->getBarcode());
            assertEquals($samples[11]->getZeitstempel(), $res[0]->getZeitstempel());
        }

        // test complex date range queries
        foreach ([
            'd:>13.03.2012 d:<15.03.2014', 'mdate:>13.03.2012 mdate:<15.03.2014'
        ] as $q) {
            $res = $search->generateSearchQuery($q)->execute();
            assertCount(3, $res);
            assertEquals($samples[2]->getBarcode(), $res[0]->getBarcode());
            assertEquals($samples[2]->getZeitstempel(), $res[0]->getZeitstempel());
            assertEquals($samples[3]->getBarcode(), $res[1]->getBarcode());
            assertEquals($samples[3]->getZeitstempel(), $res[1]->getZeitstempel());
            assertEquals($samples[4]->getBarcode(), $res[2]->getBarcode());
            assertEquals($samples[4]->getZeitstempel(), $res[2]->getZeitstempel());
        }
    }

    public function testDriveTypeQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();

        $equipment = [
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
        ];

        $factory->disableAutomaticDriveGeneration();

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00003']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00004']),
        ];

        $drives = [
            $driveFactory->create(['barcode' => 'DTHD00001', 'bauart' => 'intern']),
            $driveFactory->create(['barcode' => 'DTHD00002', 'bauart' => 'intern']),
            $driveFactory->create(['barcode' => 'DTAS00003', 'bauart' => 'extern']),
            $driveFactory->create(['barcode' => 'DTAS00004', 'bauart' => 'extern']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'type:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'type:f',
        ], $equipment, 'getBarcode');

        // test for specific
        $this->testQuery($search, [
            'type:"intern"', '!type:["extern"|"wuntern"]'
        ], [$samples[0], $samples[1]], 'getBarcode');
    }

    public function testDriveFormFactorQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();

        $equipment = [
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
        ];

        $factory->disableAutomaticDriveGeneration();

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00003']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00004']),
        ];

        $drives = [
            $driveFactory->create(['barcode' => 'DTHD00001', 'formfaktor' => '2,5']),
            $driveFactory->create(['barcode' => 'DTHD00002', 'formfaktor' => '2,5']),
            $driveFactory->create(['barcode' => 'DTAS00003', 'formfaktor' => '3,5']),
            $driveFactory->create(['barcode' => 'DTAS00004', 'formfaktor' => '3,5']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'ff:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'ff:f',
        ], $equipment, 'getBarcode');

        // test for specific
        $this->testQuery($search, [
            'ff:"2,5"', '!ff:["3,5"|4,5]'
        ], [$samples[0], $samples[1]], 'getBarcode');
    }

    public function testDriveSizeQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();

        $equipment = [
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
        ];

        $factory->disableAutomaticDriveGeneration();

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00003']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00004']),
        ];

        $drives = [
            $driveFactory->create(['barcode' => 'DTHD00001', 'groesse' => '100']),
            $driveFactory->create(['barcode' => 'DTHD00002', 'groesse' => '150']),
            $driveFactory->create(['barcode' => 'DTAS00003', 'groesse' => '200']),
            $driveFactory->create(['barcode' => 'DTAS00004', 'groesse' => '250']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'size:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'size:f',
        ], $equipment, 'getBarcode');

        // test all smaller sizes then 200
        $this->testQuery($search, [
            'size:<200', 'size:[100|150]', '!size:>150'
        ], [$samples[0], $samples[1]], 'getBarcode');

        // test all bigger sizes then 150
        $this->testQuery($search, [
            '!size:<200', '!size:[100|150]', 'size:>150'
        ], [$samples[2], $samples[3]], 'getBarcode');
    }

    public function testDriveManufacturerQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();

        $equipment = [
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
        ];

        $factory->disableAutomaticDriveGeneration();

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00003']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00004']),
        ];

        $drives = [
            $driveFactory->create(['barcode' => 'DTHD00001', 'hersteller' => 'albert']),
            $driveFactory->create(['barcode' => 'DTHD00002', 'hersteller' => 'ügürü']),
            $driveFactory->create(['barcode' => 'DTAS00003', 'hersteller' => 'übürü']),
            $driveFactory->create(['barcode' => 'DTAS00004', 'hersteller' => 'niemand']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'prod:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'prod:f',
        ], $equipment, 'getBarcode');

        // test for specific
        $this->testQuery($search, [
            'prod:"ürü"', "!prod:[albert|niemand]"
        ], [$samples[1], $samples[2]], 'getBarcode');
        $this->testQuery($search, [
            '!prod:"ürü"', "prod:[albert|niemand]"
        ], [$samples[0], $samples[3]], 'getBarcode');
        $this->testQuery($search, [
            'prod:"niemand"',
        ], [$samples[3]], 'getBarcode');
    }

    public function testDriveModelQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();

        $equipment = [
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
        ];

        $factory->disableAutomaticDriveGeneration();

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00003']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00004']),
        ];

        $drives = [
            $driveFactory->create(['barcode' => 'DTHD00001', 'modell' => 'A']),
            $driveFactory->create(['barcode' => 'DTHD00002', 'modell' => 'A']),
            $driveFactory->create(['barcode' => 'DTAS00003', 'modell' => 'B']),
            $driveFactory->create(['barcode' => 'DTAS00004', 'modell' => 'C']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'modell:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'modell:f',
        ], $equipment, 'getBarcode');

        // test for specific
        $this->testQuery($search, [
            'modell:"A"', '!modell:[B|C]',
        ], [$samples[0], $samples[1]], 'getBarcode');
    }

    public function testDriveProductNumberQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();

        $equipment = [
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
        ];

        $factory->disableAutomaticDriveGeneration();

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00003']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00004']),
        ];

        $drives = [
            $driveFactory->create(['barcode' => 'DTHD00001', 'pn' => '10']),
            $driveFactory->create(['barcode' => 'DTHD00002', 'pn' => '20']),
            $driveFactory->create(['barcode' => 'DTAS00003', 'pn' => '33']),
            $driveFactory->create(['barcode' => 'DTAS00004', 'pn' => '44']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'pn:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'pn:f',
        ], $equipment, 'getBarcode');

        // test for specific
        $this->testQuery($search, [
            'pn:"0"', '!pn:[33|44]',
        ], [$samples[0], $samples[1]], 'getBarcode');
    }

    public function testDriveSerialNumberQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();

        $equipment = [
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
        ];

        $factory->disableAutomaticDriveGeneration();

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00003']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00004']),
        ];

        $drives = [
            $driveFactory->create(['barcode' => 'DTHD00001', 'sn' => '10']),
            $driveFactory->create(['barcode' => 'DTHD00002', 'sn' => '20']),
            $driveFactory->create(['barcode' => 'DTAS00003', 'sn' => '33']),
            $driveFactory->create(['barcode' => 'DTAS00004', 'sn' => '44']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'sn:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'sn:f',
        ], $equipment, 'getBarcode');

        // test for specific
        $this->testQuery($search, [
            'sn:"0"', '!sn:[33|44]',
        ], [$samples[0], $samples[1]], 'getBarcode');
    }

    public function testDriveConnectorQuery() {
        $search = $this->getInstance();
        $factory = ObjektFactory::new();
        $driveFactory = DatentraegerFactory::new();

        $equipment = [
            $factory->equipment()->create(['barcode' => 'DTHW00001']),
            $factory->equipment()->create(['barcode' => 'DTHW00002']),
        ];

        $factory->disableAutomaticDriveGeneration();

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001']),
            $factory->hdd()->create(['barcode' => 'DTHD00002']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00003']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00004']),
        ];

        $drives = [
            $driveFactory->create(['barcode' => 'DTHD00001', 'anschluss' => 'A']),
            $driveFactory->create(['barcode' => 'DTHD00002', 'anschluss' => 'A']),
            $driveFactory->create(['barcode' => 'DTAS00003', 'anschluss' => 'B']),
            $driveFactory->create(['barcode' => 'DTAS00004', 'anschluss' => 'C']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'anschluss:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'anschluss:f',
        ], $equipment, 'getBarcode');

        // test for specific
        $this->testQuery($search, [
            'anschluss:"A"', '!anschluss:[B|C]',
        ], [$samples[0], $samples[1]], 'getBarcode');
    }

    //
    // ========= TEST COMPLEX QUERIES =========
    //
}
