<?php

namespace App\Tests\Service;

use App\Service\ExtendedAssetSearch;
use App\Tests\Factory\ObjektFactory;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use ReflectionClass;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotFalse;

class ExtendedAssetSearchTest extends KernelTestCase
{

    private function getInstance(): ExtendedAssetSearch
    {
        // (1) boot the Symfony kernel
        self::bootKernel();
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        return $container->get(ExtendedAssetSearch::class);
    }

    public function matchProvider(){
        // [query, #matches single, #matches mult]
        yield ['test', 0, 0];
        yield ['name:heinz', 1, 0];
        yield ['name:[heinz]', 0, 1];
        yield ['name:heinz name:franz cat:[0|1]', 2, 1];
        yield ['name:heinz s:[1|2] cat:[0|1]', 1, 2];
        yield ['c:"a <> äüp" c:\'a <> äüp\' c:öüäp', 3, 0];
        yield ['c:"a <> äüp\' c:"a <> äüp\' c:\'äüöp', 0, 0];

        yield ['c:[Asservat|Datentraeger] name:"HDD" || c:2 s:2', 3, 1];
    }

    public function keyValueProvider() {
        // [query, single key-val pairs, mult key-val pairs]
        yield [
            '!s:1 c:0 name="Heinz " barcode=\'DTHW32310\' c:[0|1] name:["Franz F."|\'Günther D.\'| possible]', 
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

    public function queryValueProvider() {
        // [query, parsed values]
        yield [
            '!s:1 c:0 name="Heinz " barcode=\'DTHW32310\' !c:[0|1] name:["Franz F."|\'Günther D.\'| possible]', 
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
            assertEquals($res[$i]['key'], $key);
            assertEquals($res[$i]['val'], $value);
            ++$i;
        }

        $method = (new ReflectionClass(ExtendedAssetSearch::class))->getMethod('matchKeyMultipleValue');
        $method->setAccessible(true);
        $res = $method->invokeArgs($obj, [$query]);
        $i = 0;
        foreach ($mult as $key => $value){
            assertEquals($res[$i]['key'], $key);
            assertEquals($res[$i]['val'], $value);
            ++$i;
        }
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
        
        $queries = [
            'c:1', 'k:1', 'cat:1', 'kat:1', 'category:1', 'kategorie:1',
            '!c:[0|2|3|4|5]', '!k:[0|2|3|4|5]', '!cat:[0|2|3|4|5]', '!kat:[0|2|3|4|5]', '!category:[0|2|3|4|5]', '!kategorie:[0|2|3|4|5]',
            // 'c:equipment', 'k:equipment', 'cat:equipment', 'kat:equipment', 'category:equipment', 'kategorie:equipment'
        ];
        
        foreach ($queries as $q) {
            $res = $search->parseQuery($q)->execute();
            assertCount(2, $res);
            assertEquals($samples[2]->getBarcode(), $res[0]->getBarcode());
            assertEquals($samples[2]->getKategorie(), $res[0]->getKategorie());
            assertEquals($samples[3]->getBarcode(), $res[1]->getBarcode());
            assertEquals($samples[3]->getKategorie(), $res[1]->getKategorie());
        }

        $queries = [
            'c:[2|3]', 'c:2 || c:3'
        ];
        foreach ($queries as $q) {
            $res = $search->parseQuery($q)->execute();
            assertCount(4, $res);
            assertEquals($samples[6]->getBarcode(), $res[0]->getBarcode());
            assertEquals($samples[6]->getKategorie(), $res[0]->getKategorie());
            assertEquals($samples[7]->getBarcode(), $res[1]->getBarcode());
            assertEquals($samples[7]->getKategorie(), $res[1]->getKategorie());
            assertEquals($samples[4]->getBarcode(), $res[2]->getBarcode());
            assertEquals($samples[4]->getKategorie(), $res[2]->getKategorie());
            assertEquals($samples[5]->getBarcode(), $res[3]->getBarcode());
            assertEquals($samples[5]->getKategorie(), $res[3]->getKategorie());
        }
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
            $res = $search->parseQuery($q)->execute();
            assertCount(1, $res);
            assertEquals($samples[2]->getBarcode(), $res[0]->getBarcode());
            assertEquals($samples[2]->getZeitstempel(), $res[0]->getZeitstempel());
        }

        // test simple date range queries
        foreach ([
            'd:>13.03.2021', 'mdate:>13.03.2021', '!d:<14.03.2021'
        ] as $q) {
            $res = $search->parseQuery($q)->execute();
            assertCount(1, $res);
            assertEquals($samples[11]->getBarcode(), $res[0]->getBarcode());
            assertEquals($samples[11]->getZeitstempel(), $res[0]->getZeitstempel());
        }

        // test complex date range queries
        foreach ([
            'd:>13.03.2012 d:<15.03.2014', 'mdate:>13.03.2012 mdate:<15.03.2014'
        ] as $q) {
            $res = $search->parseQuery($q)->execute();
            assertCount(3, $res);
            assertEquals($samples[2]->getBarcode(), $res[0]->getBarcode());
            assertEquals($samples[2]->getZeitstempel(), $res[0]->getZeitstempel());
            assertEquals($samples[3]->getBarcode(), $res[1]->getBarcode());
            assertEquals($samples[3]->getZeitstempel(), $res[1]->getZeitstempel());
            assertEquals($samples[4]->getBarcode(), $res[2]->getBarcode());
            assertEquals($samples[4]->getZeitstempel(), $res[2]->getZeitstempel());
        }
    }
}
