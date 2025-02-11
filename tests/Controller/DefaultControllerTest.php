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
use App\Tests\Factory\FallFactory;
use App\Tests\Factory\NutzerFactory;
use App\Tests\Factory\ObjektFactory;

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
    public function correctLoginCredentialsProvider()
    {
        yield ['name' => 'admin', 'password' => 'test'];
        yield ['name' => 'user', 'password' => 'test'];
    }

    public function invalidLoginCredentialsProvider()
    {
        yield ['name' => 'berti', 'password' => 'test'];
        yield ['name' => 'user', 'password' => '123456!'];
    }

    //
    // ================ TESTS ================
    //

    /**
     * @dataProvider correctLoginCredentialsProvider
     */
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

    /**
     * @dataProvider invalidLoginCredentialsProvider
     */
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
        // setup
        $objektFactory = ObjektFactory::new();
        $caseFactory = FallFactory::new();
        $userFactory = NutzerFactory::new();

        // create cases
        $open = $caseFactory->active()->createMany(3);
        $closed = $caseFactory->inactive()->createMany(3);

        // create objects
        $reserved = $objektFactory
            ->reservedBy($userFactory->find(['username' =>'user'])->_real())
            ->createMany(3);
        $unreserved = $objektFactory->createMany(3);

        // do request
        $client = static::createClient();
        $this->loginUser($client)->request('GET', 'http://localhost/');

        // test whether open cases are displayed
        foreach ($open as $case) {
            $this->assertSelectorTextContains('#open_cases', $case->getCaseId());
            $this->assertSelectorTextContains('#open_cases', $case->getBeschreibung());
        }
        // test whether closed cases are not displayed
        foreach ($closed as $case) {
            $this->assertSelectorTextNotContains('#open_cases', $case->getCaseId());
        }

        // test whether reserved objects are displayed
        foreach ($reserved as $obj) {
            $this->assertSelectorTextContains('#reserved_objekts', $obj->getBarcode());
            $this->assertSelectorTextContains('#reserved_objekts', $obj->getKategorieName());
            $this->assertSelectorTextContains('#reserved_objekts', $obj->getName());
        }
        // test whether not-reserved objects are displayed
        foreach ($unreserved as $obj) {
            $this->assertSelectorTextNotContains('#reserved_objekts', $obj->getBarcode());
        }
    }
}
