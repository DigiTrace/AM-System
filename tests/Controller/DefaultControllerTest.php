<?php
   // AM-System
   // Copyright (C) 2019 Robert Krasowski
   // This program was created during an internship at DigiTrace GmbH
   // Read LIZENZ.txt for full notice

   // This program is free software: you can redistribute it and/or modify
   // it under the terms of the GNU General Public License as published by
   // the Free Software Foundation, either version 3 of the License, or
   // (at your option) any later version.

   // This program is distributed in the hope that it will be useful,
   // but WITHOUT ANY WARRANTY; without even the implied warranty of
   // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   // GNU General Public License for more details.

   // You should have received a copy of the GNU General Public License
   // along with this program.  If not, see <http://www.gnu.org/licenses/>.

namespace App\Tests\Controller;

use App\Tests\_support\BaseWebTestCase;
use App\Tests\Factory\CaseFactory;
use App\Tests\Factory\NutzerFactory;
use App\Tests\Factory\AssetFactory;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @author Ben Brooksnieder
 */
class DefaultControllerTest extends BaseWebTestCase
{
    //
    // ================ DATA PROVIDERS ================
    //

    /**
     * @see \App\Tests\Story\DefaultUserStory
     */
    public static function correctLoginCredentialsProvider()
    {
        yield ['name' => 'admin', 'password' => 'test'];
        yield ['name' => 'user', 'password' => 'test'];
    }

    public static function invalidLoginCredentialsProvider()
    {
        yield ['name' => 'berti', 'password' => 'test'];
        yield ['name' => 'user', 'password' => '123456!'];
    }

    //
    // ================ TESTS ================
    //

    #[DataProvider("correctLoginCredentialsProvider")]
    public function testLoginWithCorrectCredentials($name, $password){
        $client = static::createClient();
        
        $crawler = $client->request('GET', 'http://localhost/login');
        $form = $crawler->selectButton('security.login.submit')->form();
        $client->submit($form, [
            '_username' => $name,
            '_password' => $password
        ]);
        $this->assertResponseRedirects('http://localhost/'); 
        $client->followRedirect();
        $this->assertSelectorTextContains('#myNavbar', $name);
    }

    #[DataProvider("invalidLoginCredentialsProvider")]
    public function testLoginWithIncorrectCredentials($name, $password){
        $client = static::createClient();
        
        $crawler = $client->request('GET', 'http://localhost/login');
        $form = $crawler->selectButton('security.login.submit')->form();
        $client->submit($form, [
            '_username' => $name,
            '_password' => $password
        ]);
        $this->assertResponseRedirects('http://localhost/login');
        $crawler = $client->followRedirect();
        $this->assertSelectorTextContains('div.alert.alert-danger', 'Invalid credentials.');

        // test home
        $client->request('GET', 'http://localhost/');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
    
    public function testLogout()
    {
        $client = static::createClient();
        $this->loginUser($client);
        // request home successfully
        $client->request('GET', 'http://localhost/');
        $this->assertResponseIsSuccessful();
        // logout
        $client->request('GET', '/logout');
        $this->assertResponseRedirects('http://localhost/', 302);
        // request home unsuccessfully
        $client->request('GET', 'http://localhost/');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testDashboard()
    {
        $client = static::createClient();
        // setup
        $assetFactory = AssetFactory::new();
        $caseFactory = CaseFactory::new();
        $userFactory = NutzerFactory::new();

        // create cases
        /**
         * @var \App\Entity\CaseFile[]
         */
        $open = $caseFactory->active()->many(3)->create();
        /**
         * @var \App\Entity\CaseFile[]
         */
        $closed = $caseFactory->inactive()->many(3)->create();

        // create assets
        /**
         * @var \App\Entity\Asset[]
         */
        $reserved = $assetFactory
            ->reservedBy($userFactory->find(['username' =>'user'])->_real())
            ->many(3)->create();
        /**
         * @var \App\Entity\Asset[]
         */
        $unreserved = $assetFactory->many(3)->create();

        // do request
        $this->loginUser($client)->request('GET', 'http://localhost/');

        // test whether open cases are displayed
        foreach ($open as $case) {
            $this->assertSelectorTextContains('#open_cases', $case->getCaseId());
            $this->assertSelectorTextContains('#open_cases', $case->getDescription());
        }
        // test whether closed cases are not displayed
        foreach ($closed as $case) {
            $this->assertSelectorTextNotContains('#open_cases', $case->getCaseId());
        }

        // test whether reserved objects are displayed
        foreach ($reserved as $asset) {
            $this->assertSelectorTextContains('#reserved_objekts', $asset->getBarcode());
            $this->assertSelectorTextContains('#reserved_objekts', $asset->getCategory()->toTranslatableString());
            $this->assertSelectorTextContains('#reserved_objekts', $asset->getName());
        }
        // test whether not-reserved objects are displayed
        foreach ($unreserved as $asset) {
            $this->assertSelectorTextNotContains('#reserved_objekts', $asset->getBarcode());
        }
    }
}
