<?php

namespace App\Tests\Service;

use App\Enum\AssetState as State;
use App\Service\ExtendedCaseSearch;
use App\Tests\Factory\DriveFactory;
use App\Tests\Factory\CaseFactory;
use App\Tests\Factory\NutzerFactory;
use App\Tests\Factory\AssetFactory;
use DateTime;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertSameSize;

class ExtendedCaseSearchTest extends KernelTestCase
{
    // helper function
    private function getInstance(): ExtendedCaseSearch
    {
        // (1) boot the Symfony kernel
        self::bootKernel();
        // (2) use static::getContainer() to access the service container
        $container = static::getContainer();

        // (3) run some service & test the result
        return $container->get(ExtendedCaseSearch::class);
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
            $set[$element->getId()] = $element->$method();
        }

        foreach ($result as $element) {
            // test all expected items to find match
            $barcode = $element->getId();
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
                    assertEquals($set[$barcode]->getId(), $value->getId(), "Asserting $method assets have same barcodes has failed for asset $barcode.");
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
     * @param \App\Service\ExtendedCaseSearch $search
     * @param array $queries
     * @param array $expected
     * @param string $method
     * @return void
     */
    private function testQuery(ExtendedCaseSearch $search, array $queries, array $expected, string $method){
        foreach ($queries as $q) {
            $query = $search->generateSearchQuery($q);
            $res = $query->execute();

            if(empty($expected)){
                assertEmpty($res, "query '$q' did not return empty result");
            }
            else {
                $this->assertAttributeInSet($expected, $res, $method);
            }
        }
    }

    public static function extendedSearchCheckProvider() {
        // [query, isExtended]
        yield ['suche', false];
        yield ['komplizierter suchterm', false];
        yield ['c:1', true];
        yield ['dasist:einekomplexesuche', true];
    }

    #[DataProvider("extendedSearchCheckProvider")]
    public function testIsExtended($query, $isExtended){
        $search = $this->getInstance();
        assertEquals($isExtended, $search->isExtendedQuery($query));
    }

    //
    // ========= TEST QUERY METHODS =========
    //

    public function testCaseIdQuery(){
        $this->markTestIncomplete('Not yet implemented');
    }

    public function testDescriptionQuery(){
        $this->markTestIncomplete('Not yet implemented');
    }

    public function testActiveQuery(){
        $this->markTestIncomplete('Not yet implemented');
    }

    public function testOpenendOnQuery(){
        $this->markTestIncomplete('Not yet implemented');
    }

    //
    // ========= TEST COMPLEX QUERIES =========
    //
}
