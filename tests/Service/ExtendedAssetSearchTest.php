<?php

namespace App\Tests\Service;

use App\Entity\Asset;
use App\Enum\AssetCategory as Category;
use App\Enum\AssetState as State;
use App\Service\ExtendedAssetSearch;
use App\Tests\Factory\DriveFactory;
use App\Tests\Factory\FallFactory;
use App\Tests\Factory\NutzerFactory;
use App\Tests\Factory\AssetFactory;
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
     * Helper function to assert that two sets have the same assets in relation to $method attribute.
     * 
     * @param array  $expected Set of expected assets
     * @param array  $result   Set of result assets
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
            assertArrayHasKey($barcode, $set, "Asset $barcode was not expected to be found.");
            $value = $element->$method();
            // test primitive or asset
            if (is_object($value)){
                // test for modifiedby objetcs
                if(method_exists($set[$barcode], 'getId')){
                    assertEquals($set[$barcode]->getId(), $value->getId(), "Asserting $method assets have same ids has failed for asset $barcode.");
                }
                // ... or regular assets
                else if(method_exists($set[$barcode], 'getBarcode')) {
                    assertEquals($set[$barcode]->getBarcode(), $value->getBarcode(), "Asserting $method assets have same barcodes has failed for asset $barcode.");
                }
                else {
                    assertEquals($set[$barcode], $value);
                }
            }
            else {
                assertEquals($set[$barcode], $value, "Asserting $method property is equal has failed for asset $barcode.");
            }
            // remove found element from result set
            unset($set[$barcode]);
        }

        assertEmpty($set);
    }

    /**
     * Test query with extended asset search and verify in asset to $method
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
        $factory = AssetFactory::new();

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
        ], [$samples[3], $samples[2]], 'getCategory');

        // test multiple 
        $this->testQuery($search, [
            'c:[2|3]', 'c:2 || c:3'
        ], [$samples[4], $samples[5], $samples[6], $samples[7]], 'getCategory');
    }

    public function testStateQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();

        // create assets with state 4 and 0
        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'state' => State::Added]),                // 0
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'state' => State::Added]),                // 1
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'state' => State::HandoverPerson]),    // 2
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'state' => State::HandoverPerson]),    // 3
        ];
        
        $this->testQuery($search, [
            's:4', 'state:4',
            '!s:[0|1|2|3|5|6|7|8|9|10|11|12|13|14]',
        ], [$samples[2], $samples[3]], 'getState');
    }

    public function testBarcodeQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();

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
        $factory = AssetFactory::new();

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

    public function testUsageQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'usage' => 'Dies ist ein text mit leerzeichen']),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'usage' => 'Komische Zahlen 23123!']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'usage' => '']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'usage' => 'xyz']),
        ];
        
        $this->testQuery($search, [
            'desc:xyz', 'description:"xyz"'
        ], [$samples[3]], 'getUsage');

        $this->testQuery($search, [
            'desc:"Dies ist ein text mit leerzeichen"'
        ], [$samples[0]], 'getUsage');

        $this->testQuery($search, [
            'desc:"123!"'
        ], [$samples[1]], 'getUsage');

        $this->testQuery($search, [
            '!desc:" "'
        ], [$samples[2], $samples[3]], 'getUsage');
    }

    public function testFormerUsageQuery() {
        $this->markTestIncomplete();
    }

    public function testModifiedByQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();

        $NutzerFactory = NutzerFactory::new();
        $alice = $NutzerFactory->enabled()->testPassword()->with([
            'username' => 'alice',
            'fullname' => 'alice',
            'email' => 'alice@localhost',
        ])->create()->_real();
        $bob = $NutzerFactory->enabled()->testPassword()->with([
            'username' => 'bob',
            'fullname' => 'bob',
            'email' => 'bob@localhost',
        ])->create()->_real();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'modifiedby' => $alice]),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'modifiedby' => $alice]),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'modifiedby' => $bob]),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'modifiedby' => $bob]),
        ];
        
        $this->testQuery($search, [
            'u:alice', '!u:bob'
        ], [$samples[0], $samples[1]], 'getModifiedBy');
        
        $this->testQuery($search, [
            '!u:alice', 'u:bob'
        ], [$samples[2], $samples[3]], 'getModifiedBy');
    }

    public function testFormerModifiedByQuery() {
        $this->markTestIncomplete();
    }

    public function testReservedByQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();

        $NutzerFactory = NutzerFactory::new();
        $alice = $NutzerFactory->enabled()->testPassword()->with([
            'username' => 'alice',
            'fullname' => 'alice',
            'email' => 'alice@localhost',
        ])->create()->_real();
        $bob = $NutzerFactory->enabled()->testPassword()->with([
            'username' => 'bob',
            'fullname' => 'bob',
            'email' => 'bob@localhost',
        ])->create()->_real();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'state' => State::Reserved, 'reservedBy' => $alice]),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'state' => State::Reserved, 'reservedBy' => $bob]),
            $factory->equipment()->create(['barcode' => 'DTHW00001', 'state' => State::UnbindReservation]),
            $factory->equipment()->create(['barcode' => 'DTHW00002', 'state' => State::UnbindReservation]),
            $factory->container()->create(['barcode' => 'DTHW00003']),
            $factory->container()->create(['barcode' => 'DTHW00004']),
        ];
        
        $this->testQuery($search, [
            'r:t'
        ], [$samples[0], $samples[1]], 'getReservedBy');

        $this->testQuery($search, [
            'r:f'
        ], [$samples[2], $samples[3], $samples[4], $samples[5]], 'getReservedBy');
        
        $this->testQuery($search, [
            'r:alice',
        ], [$samples[0]], 'getReservedBy');
        
        $this->testQuery($search, [
            'r:bob',
        ], [$samples[1]], 'getReservedBy');
        
        // empty results
        $this->testQuery($search, [
            'r:andreas',
        ], [], 'getReservedBy');
    }

    public function testFormerReservedQuery() {
        $this->markTestIncomplete();
    }

    public function testLocationQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();

        $container = [
            $factory->container()->create(['barcode' => 'DTHW00003']),
            $factory->container()->create(['barcode' => 'DTHW00004']),
        ];
        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001', 'state' => State::StoredInContainer, 'location' => $container[0]]),
            $factory->hdd()->create(['barcode' => 'DTHD00002', 'state' => State::StoredInContainer, 'location' => $container[0]]),
            $factory->record()->create(['barcode' => 'DTAS00003', 'state' => State::StoredInContainer, 'location' => $container[1]]),
            $factory->record()->create(['barcode' => 'DTAS00004', 'state' => State::StoredInContainer, 'location' => $container[1]]),
        ];
        
        // test all
        $this->testQuery($search, [
            'l:t', 'location:t', 'l:[DTHW00003|DTHW00004]', 'l:DTHW'
        ], $samples, 'getLocation');
        
        // test container
        $this->testQuery($search, [
            'l:f', 
        ], $container, 'getLocation');
        
        // test single
        $this->testQuery($search, [
            'l:"DTHW00003"'
        ], [$samples[0], $samples[1]], 'getLocation');
    }
    
    public function testFormerLocationQuery() {
        $this->markTestIncomplete();
    }

    public function testCaseQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();
        $caseFactory = FallFactory::new();

        $cases = [
            $caseFactory->active()->create(['case_id' => 'Fall 1']),
            $caseFactory->active()->create(['case_id' => 'Fall 2']),
        ];

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001', 'state' => State::AssignedCase, 'case' => $cases[0]]),
            $factory->hdd()->create(['barcode' => 'DTHD00002', 'state' => State::PulledOutOfContainer, 'case' => $cases[1]]),
            $factory->record()->create(['barcode' => 'DTAS00003']),
            $factory->record()->create(['barcode' => 'DTAS00004']),
        ];
        
        // test set
        $this->testQuery($search, [
            'case:t', 'case:["Fall 1"|"Fall 2"]', 'case:Fall'
        ], [$samples[0], $samples[1]], 'getCase');
        
        // test not set
        $this->testQuery($search, [
            'case:f' 
        ], [$samples[2], $samples[3]], 'getCase');
        
        // test single
        $this->testQuery($search, [
            'case:"Fall 1"', '!case:"Fall 2"'
        ], [$samples[0]], 'getCase');
    }
    
    public function testFormerCaseQuery() {
        $this->markTestIncomplete();
    }

    public function testCaseActiveQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();
        $caseFactory = FallFactory::new();

        $cases = [
            $caseFactory->active()->create(['case_id' => 'Aktiv 1']),
            $caseFactory->inactive()->create(['case_id' => 'Aktiv 2']),
        ];

        $samples = [
            $factory->hdd()->create(['barcode' => 'DTHD00001', 'state' => State::AssignedCase, 'case' => $cases[0]]),
            $factory->hdd()->create(['barcode' => 'DTHD00002', 'state' => State::PulledOutOfContainer, 'case' => $cases[1]]),
            $factory->record()->create(['barcode' => 'DTAS00003']),
            $factory->record()->create(['barcode' => 'DTAS00004']),
        ];
        
        // test active
        $this->testQuery($search, [
            'caseactive:t'
        ], [$samples[0]], 'getCase');
        
        // test not set
        $this->testQuery($search, [
            'caseactive:f' 
        ], [$samples[1]], 'getCase');
    }

    public function testNoteQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();

        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'note' => 'Dies ist ein text mit leerzeichen']),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'note' => 'Komische Zahlen 23123!']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'note' => '']),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'note' => 'xyz']),
        ];
        
        $this->testQuery($search, [
            'info:xyz', 'note:"xyz"'
        ], [$samples[3]], 'getNote');

        $this->testQuery($search, [
            'note:"Dies ist ein text mit leerzeichen"'
        ], [$samples[0]], 'getNote');

        $this->testQuery($search, [
            'note:"123!"'
        ], [$samples[1]], 'getNote');

        $this->testQuery($search, [
            '!note:" "'
        ], [$samples[2], $samples[3]], 'getNote');
    }

    public function testDateQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();

        // create assets with category 1 and not 1
        $samples = [
            $factory->exhibit()->create(['barcode' => 'DTAS00001', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2010-03-14')]),
            $factory->exhibit()->create(['barcode' => 'DTAS00002', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2011-03-14')]),
            $factory->equipment()->create(['barcode' => 'DTHW00001', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2012-03-14')]),
            $factory->equipment()->create(['barcode' => 'DTHW00002', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2013-03-14')]),
            $factory->container()->create(['barcode' => 'DTHW00003', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2014-03-14')]),
            $factory->container()->create(['barcode' => 'DTHW00004', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2015-03-14')]),
            $factory->hdd()->create(['barcode' => 'DTHD00001', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2016-03-14')]),
            $factory->hdd()->create(['barcode' => 'DTHD00002', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2017-03-14')]),
            $factory->record()->create(['barcode' => 'DTAS00003', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2018-03-14')]),
            $factory->record()->create(['barcode' => 'DTAS00004', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2019-03-14')]),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00005', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2020-03-14')]),
            $factory->exhibitHdd()->create(['barcode' => 'DTAS00006', 'lastupdatedon' => DateTime::createFromFormat('Y-m-d', '2021-03-14')]),
        ];
        
        // test simple single date queries
        $this->testQuery($search, [
            'd:14.03.2012', 'mdate:14.03.12', '!d:[<14.03.2012|>14.03.2012]'
        ], [$samples[2]], 'getLastUpdatedOn');

        // test simple date range queries
        $this->testQuery($search, [
            'd:>13.03.2021', 'mdate:>03/13/2021', '!d:<14.03.21', 'ed:>=2021-03-14'
        ], [$samples[11]], 'getLastUpdatedOn');

        // test complex date range queries
        $this->testQuery($search, [
            'd:>03/13/2012 d:<03/15/14', 'mdate:>13.03.2012 mdate:<15.03.2014', 'date:>=14.03.2012 ed:<=14.03.2014'
        ], [$samples[2], $samples[3], $samples[4]], 'getLastUpdatedOn');
    }

    public function testDriveTypeQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();
        $driveFactory = DriveFactory::new();

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
            $driveFactory->create(['barcode' => $samples[0]->_real(), 'type' => 'intern']),
            $driveFactory->create(['barcode' => $samples[1]->_real(), 'type' => 'intern']),
            $driveFactory->create(['barcode' => $samples[2]->_real(), 'type' => 'extern']),
            $driveFactory->create(['barcode' => $samples[3]->_real(), 'type' => 'extern']),
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
        $factory = AssetFactory::new();
        $driveFactory = DriveFactory::new();

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
            $driveFactory->create(['barcode' => $samples[0]->_real(), 'formFactor' => '2,5']),
            $driveFactory->create(['barcode' => $samples[1]->_real(), 'formFactor' => '2,5']),
            $driveFactory->create(['barcode' => $samples[2]->_real(), 'formFactor' => '3,5']),
            $driveFactory->create(['barcode' => $samples[3]->_real(), 'formFactor' => '3,5']),
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
        $factory = AssetFactory::new();
        $driveFactory = DriveFactory::new();

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
            $driveFactory->create(['barcode' => $samples[0]->_real(), 'size' => '100']),
            $driveFactory->create(['barcode' => $samples[1]->_real(), 'size' => '150']),
            $driveFactory->create(['barcode' => $samples[2]->_real(), 'size' => '200']),
            $driveFactory->create(['barcode' => $samples[3]->_real(), 'size' => '250']),
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
            'size:<200', 'size:<=150', 'size:[100|150]', '!size:>150'
        ], [$samples[0], $samples[1]], 'getBarcode');

        // test all bigger sizes then 150
        $this->testQuery($search, [
            '!size:<200', '!size:[100|150]', 'size:>150', 'size:>=200',
        ], [$samples[2], $samples[3]], 'getBarcode');
    }

    public function testDriveManufacturerQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();
        $driveFactory = DriveFactory::new();

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
            $driveFactory->create(['barcode' => $samples[0]->_real(), 'manufacturer' => 'albert']),
            $driveFactory->create(['barcode' => $samples[1]->_real(), 'manufacturer' => 'ügürü']),
            $driveFactory->create(['barcode' => $samples[2]->_real(), 'manufacturer' => 'übürü']),
            $driveFactory->create(['barcode' => $samples[3]->_real(), 'manufacturer' => 'niemand']),
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
        $factory = AssetFactory::new();
        $driveFactory = DriveFactory::new();

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
            $driveFactory->create(['barcode' => $samples[0]->_real(), 'model' => 'A']),
            $driveFactory->create(['barcode' => $samples[1]->_real(), 'model' => 'A']),
            $driveFactory->create(['barcode' => $samples[2]->_real(), 'model' => 'B']),
            $driveFactory->create(['barcode' => $samples[3]->_real(), 'model' => 'C']),
        ];

        $factory->enableAutomaticDriveGeneration();

        // test all
        $this->testQuery($search, [
            'model:t',
        ], $samples, 'getBarcode');

        // test all non drives
        $this->testQuery($search, [
            'model:f',
        ], $equipment, 'getBarcode');

        // test for specific
        $this->testQuery($search, [
            'model:"A"', '!model:[B|C]',
        ], [$samples[0], $samples[1]], 'getBarcode');
    }

    public function testDriveProductNumberQuery() {
        $search = $this->getInstance();
        $factory = AssetFactory::new();
        $driveFactory = DriveFactory::new();

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
            $driveFactory->create(['barcode' => $samples[0]->_real(), 'productNumber' => '10']),
            $driveFactory->create(['barcode' => $samples[1]->_real(), 'productNumber' => '20']),
            $driveFactory->create(['barcode' => $samples[2]->_real(), 'productNumber' => '33']),
            $driveFactory->create(['barcode' => $samples[3]->_real(), 'productNumber' => '44']),
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
        $factory = AssetFactory::new();
        $driveFactory = DriveFactory::new();

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
            $driveFactory->create(['barcode' => $samples[0]->_real(), 'serialNumber' => '10']),
            $driveFactory->create(['barcode' => $samples[1]->_real(), 'serialNumber' => '20']),
            $driveFactory->create(['barcode' => $samples[2]->_real(), 'serialNumber' => '33']),
            $driveFactory->create(['barcode' => $samples[3]->_real(), 'serialNumber' => '44']),
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
        $factory = AssetFactory::new();
        $driveFactory = DriveFactory::new();

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
            $driveFactory->create(['barcode' => $samples[0]->_real(), 'connector' => 'A']),
            $driveFactory->create(['barcode' => $samples[1]->_real(), 'connector' => 'A']),
            $driveFactory->create(['barcode' => $samples[2]->_real(), 'connector' => 'B']),
            $driveFactory->create(['barcode' => $samples[3]->_real(), 'connector' => 'C']),
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
