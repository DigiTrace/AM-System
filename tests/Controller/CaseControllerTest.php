<?php

namespace App\Tests\Controller;

use App\Repository\FallRepository;
use App\Tests\_support\BaseWebTestCase;
use App\Tests\Factory\FallFactory;

/**
 * @author Ben Brooksnieder
 */
class CaseControllerTest extends BaseWebTestCase
{
    public function genericUrlProvider()
    {
        yield ['/faelle'];
        yield ['/faelle/faq'];
        yield ['/fall/anlegen'];
    }

    public function detailUrlProvider()
    {
        yield ['/fall/%s/anzeigen/'];
        yield ['/fall/%s/aktualisieren/'];
        yield ['/fall/%s/downloadWord/'];
    }

    public function validCaseProvider()
    {
        yield [['id' => 'XIVv2', 'desc' => '(TEST)Computersabotage']];
        yield [['id' => 'TLG', 'desc' => '(TEST)Einbruch im Hochsicherheitstrakt beim HIER BEKANNTE FIRMA EINTRAGEN. Laptop mit HIER WICHTIGE DATENBESTAND EINFÜGEN Daten entwendet']];
        yield [['id' => 'Müller/c1', 'desc' => '(TEST)Auf seinen privaten Rechner wurde eine Bitcoinsoftware per Malware installiert']];
        yield [['id' => '78/98', 'desc' => '(TEST)Verdacht auf Besitz von KiPo']];
        yield [['id' => 'Schmidt AG', 'desc' => '(TEST)Pentest des Front Webservers']];
    }

    public function invalidCaseProvider()
    {
        yield 'empty ID' => [['id' => '', 'desc' => 'Fall ohne ID darf es nicht geben']];
        yield 'empty description' => [['id' => 'Fall ohne Beschreibung darf es nicht geben', 'desc' => '']];
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
        $factory = FallFactory::new();
        $case = $factory->active()->create();

        $client = static::createClient();
        $this->loginUser($client)->request('GET', sprintf($url, $case->getCaseId()));

        $this->assertResponseIsSuccessful();
    }

    /**
     * @dataProvider validCaseProvider
     */
    public function testAddCaseValid($params)
    {
        // setup
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/fall/anlegen');

        // make form request
        $form = $crawler->selectButton('add_new_case')->form();
        $client->submit($form, [
            'form[case_id]' => $params['id'],
            'form[beschreibung]' => $params['desc'],
        ]);
        $this->assertResponseRedirects("/faelle");
        $client->followRedirect();
        

        // look into hmtl whether case was rendered and processed correctly
        $this->assertSelectorTextContains("tr:contains('{$params['id']}')", $params['desc']);

        $this->seeInDatabase(FallRepository::class, [
            'case_id' => $params['id'],
            'beschreibung' => $params['desc'],
        ]);

        return $client;
    }

    /**
     * @dataProvider invalidCaseProvider
     *
     * @depends testAddCaseValid
     */
    public function testAddCaseInvalid($params)
    {
        // setup
        $client = static::createClient();
        $crawler = $this->loginUser($client)->request('GET', '/fall/anlegen');

        // make form request
        $form = $crawler->selectButton('add_new_case')->form();
        $client->submit($form, [
            'form[case_id]' => $params['id'],
            'form[beschreibung]' => $params['desc'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('span.glyphicon-exclamation-sign');
        $this->dontSeeInDatabase(FallRepository::class, [
            'case_id' => $params['id'],
            'beschreibung' => $params['desc'],
        ]);
        return $client;
    }

    /**
     * @depends testAddCaseInvalid
     */
    public function testAddCaseDuplicate()
    {
        // setup
        $params = [
            'id' => 'Stockholmer Brandanschlag',
            'desc' => 'Festplatte des Brandstifters auf Motive untersuchen',
        ];

        // add case first time successful
        $client = $this->testAddCaseValid($params);

        // make second entry
        $crawler = $client->request('GET', '/fall/anlegen');
        $form = $crawler->selectButton('add_new_case')->form();
        $client->submit($form, [
            'form[case_id]' => $params['id'],
            'form[beschreibung]' => $params['desc'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-danger', 'used');

        $this->seeInDatabase(FallRepository::class, [
            'case_id' => $params['id'],
            'beschreibung' => $params['desc'],
        ], 1);

        return $client;
    }
}
