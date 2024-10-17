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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use \App\Controller\helper;

class DefaultControllerTest extends WebTestCase
{
    private static $EM2;
    
    public static function setUpBeforeClass() :void {
        static::$kernel = static::createKernel();
        static::$kernel -> boot();
        DefaultControllerTest::$EM2 = static::$kernel->getContainer()
                                     ->get('doctrine')
                                     ->getManager();
        DefaultControllerTest::regenerateSchema();
        
        $admin = new \App\Entity\Nutzer();
                
        // Salted password is "test"
        $admin->SetCustom($name = 'Admin',
                          $fullname = 'Testadmin',
                             $email = 'admin@localhost',
                             $role = ['ROLE_ADMIN'],
                             $saltedpassword = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2');
        
        $user = new \App\Entity\Nutzer();
        
        // Salted password is "test"
        $user->SetCustom($name = 'User',
                         $fullname = 'Testuser',
                             $email = 'user@localhost',
                             $role = ['ROLE_USER'],
                             $saltedpassword = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2');
       
        $user2 = new \App\Entity\Nutzer();
        // Salted password is "test"
        $user2->SetCustom($name = 'user2',
                         $fullname = 'Testuser2',
                             $email = 'user2@localhost',
                             $role = ['ROLE_USER'],
                             $saltedpassword = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2');
       
        $user3 = new \App\Entity\Nutzer();
        // Salted password is "test"
        $user3->SetCustom($name = 'Üser3',
                         $fullname = 'Testuser3',
                             $email = 'user3@localhost',
                             $role = ['ROLE_USER'],
                             $saltedpassword = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2');
       
        
        
        DefaultControllerTest::$EM2->persist($admin);
        DefaultControllerTest::$EM2->persist($user);
        DefaultControllerTest::$EM2->persist($user2);
        DefaultControllerTest::$EM2->persist($user3);
        
        DefaultControllerTest::$EM2->flush();
        
    }

    public static function tearDownAfterClass() :void {
        parent::tearDownAfterClass();
        DefaultControllerTest::$EM2 -> close();
        
    }
    
    /**
     * Drops current schema and creates a brand new one
     */
    private static function regenerateSchema() {
        $metadatas = DefaultControllerTest::$EM2->getMetadataFactory()->getAllMetadata();
        if (!empty($metadatas)) {
            $tool = new \Doctrine\ORM\Tools\SchemaTool(DefaultControllerTest::$EM2);
            $tool -> dropSchema($metadatas);
            $tool -> createSchema($metadatas);
        }
    }
    
    private function loginWithCorrectCredentials($name,$password){
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('security.login.submit')->form();
        $client->submit($form, array('_username' => $name,
                                     '_password' => $password));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $client->followRedirect();
        
        return $client;
    }
    
    private function logoutCorrect($client){
        
        $client->request('GET', '/logout');
        
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/'));
        $client->followRedirect();
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
        $client->followRedirect();
    }
    
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        
        // Man ist nicht authentisiert, von daher soll man auf die Loginseite
        // verwiesen werden.        
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        
        $this->assertTrue($client->getResponse()->isRedirect());
       
        $client->followRedirect();
        
        // Wenn das hier statt 200, 500 als Ausgabe kommt, liegt das hoechst
        // wahrscheinlich daran, dass phpunit nicht mit --stderr ausgefuehrt
        // worden ist. Grund fuer ist, dass phpunit selbst Sessions benoetigt,
        // die Webseite des Logins selbst braucht
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('security.login.username', $client->getResponse()->getContent());
    }
    
    public function testCorrectLoginAdmin()
    {
        $nutzername = "Admin";
        $passwort = "test";
        
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('security.login.submit')->form();
        $client->submit($form, array('_username' => $nutzername,
                                     '_password' => $passwort));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        //echo $client->getResponse()->getContent();
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/'));
        
        
        $client->followRedirect();
        $crawler = $client->getCrawler();
        
        
        $query = $crawler->filter("li:contains('".$nutzername."')")->text();
        
        $this->assertStringContainsString($nutzername,$query); 
    }
    
    public function testCorrectLoginUser()
    {
        $nutzername = "User";
        $passwort = "test";
        
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('security.login.submit')->form();
        $client->submit($form, array('_username' => $nutzername,
                                     '_password' => $passwort));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        //echo $client->getResponse()->getContent();
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/'));
        
        
        $client->followRedirect();
        $crawler = $client->getCrawler();
        
        
        $query = $crawler->filter("li:contains('".$nutzername."')")->text();
        
        $this->assertStringContainsString($nutzername,$query); 
    }
    
    
    
    
    public function testCorrectLogout(){
        $client = $this->loginWithCorrectCredentials("user","test");
        $client->request('GET', '/logout');
        
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/'));
        $client->followRedirect();
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
        //echo $client->getResponse()->headers->get('location');
        
    }
    
    
    
    // Als nicht registrierter Nutzer soll dieser bei jedem Aufruf auf die Login
    // Seite verwiesen werden
    public function testAnonymousActions(){
        $client = static::createClient();
        
        $crawler = $client->request('GET', '/objekte');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
        
        $crawler = $client->request('GET', '/objekt/DTAS66666');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
        
        $crawler = $client->request('GET', '/faelle');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
        
        $crawler = $client->request('GET', '/fall/Blablabla/anzeigen');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
        
        $crawler = $client->request('GET', '/objekt/DTAS7777/vernichtet');
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));
    }

    
    
    public function testIncorrectLogin(){
        $nutzername = "Admin";
        $passwort = "BLABLABLA";
        
        $client = static::createClient();
       
        
        
        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('security.login.submit')->form();
        $client->submit($form, array('_username' => $nutzername,
                                     '_password' => $passwort));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'));        
    }
    
    
    
    public function testnavigateToOverviewObjects(){
        $client = $this->loginWithCorrectCredentials("user","test");
        $client->request('GET', '/objekte');
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString(
            'objectsummary:',
            $client->getResponse()->getContent()
        ); 
        $this->logoutCorrect($client);
    }
    
   
    
    public function testnavigateToOverviewCases(){
        // KNP Sortierung deaktivieren, da dies Fehler mit PHPUnit Tests 
        // verursacht
        unset($_GET['sort']);
        
        $client = $this->loginWithCorrectCredentials("user","test");
        $client->request('GET', '/faelle');
        
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString(
            'casesummary',
            $client->getResponse()->getContent()
        );
        $this->logoutCorrect($client);
    }
    
    private function AddCorrectCase($parameters,$client){
       
        $crawler = $client->request('POST', 'http://localhost/fall/anlegen');
        $form = $crawler->selectButton('add_new_case')->form();
        $client->submit($form, array('form[case_id]' => $parameters['id'],
                                     'form[beschreibung]' => $parameters['desc']));
        
        $this->assertTrue($client->getResponse()->isRedirect("/faelle"));
        
        
        $crawler = $client->request('POST', '/faelle');

        
        # TODO: den Test besser verstehen
        $query = $crawler->filter("tr:contains('".$parameters['id']."')")->text();
        $this->assertNotFalse($query);
        $this->assertStringContainsString($parameters['id'],$query);
        $this->assertStringContainsString($parameters['desc'],$query);
    }
    
    public function testAddCorrectCase1()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['id']= "XIVv2";
        $parameters['desc'] = "(TEST)Computersabotage";
        
        $this->AddCorrectCase($parameters, $client);
        
        $this->logoutCorrect($client);
    }
     
    
    public function testAddCorrectCase2()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['id']= "TLG";
        $parameters['desc'] = "(TEST)Einbruch im Hochsicherheitstrakt beim HIER "
                . "BEKANNTE FIRMA EINTRAGEN. Laptop mit HIER WICHTIGE "
                . "DATENBESTAND EINFÜGEN Daten entwendet";
        
        $this->AddCorrectCase($parameters, $client);
        $this->logoutCorrect($client);
    }
	
	public function testAddCorrectCase3()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['id']= "Müller/c1";
        $parameters['desc'] = "(TEST)Auf seinen privaten Rechner wurde eine Bitcoinsoftware ".
							 "per Malware installiert";
        
        $this->AddCorrectCase($parameters, $client);
        $this->logoutCorrect($client);
    }
    
	public function testAddCorrectCase4()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['id']= "78/98";
        $parameters['desc'] = "(TEST)Verdacht auf Besitz von KiPo";
        
        $this->AddCorrectCase($parameters, $client);
        $this->logoutCorrect($client);
    }
	
	public function testAddCorrectCase5()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['id']= "Schmidt AG";
        $parameters['desc'] = "(TEST)Pentest des Front Webservers";
        
        $this->AddCorrectCase($parameters, $client);
        $this->logoutCorrect($client);
    }
	
    public function testAddIncorrectCaseEmptyDescription()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        $crawler = $client->request('POST', 'http://localhost/fall/anlegen');
        $form = $crawler->selectButton('add_new_case')->form();
        $client->submit($form, array('form[case_id]' => 'Fall ohne Beschreibung darf es nicht geben',
                                     'form[beschreibung]' => ''));
        
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $this->assertFalse($client->getResponse()->isRedirect("/faelle"));
        
        $this->logoutCorrect($client);
    }
    
    public function testAddIncorrectCaseEmptyId()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        $crawler = $client->request('POST', 'http://localhost/fall/anlegen');
        $form = $crawler->selectButton('add_new_case')->form();
        $client->submit($form, array('form[case_id]' => '',
                                     'form[beschreibung]' => 'Fall ohne ID darf es nicht geben'));
        
        
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $this->assertFalse($client->getResponse()->isRedirect("/faelle"));
        $this->logoutCorrect($client);
    }
    
    
    public function testAddDuplicatedCase()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        $crawler = $client->request('POST', 'http://localhost/fall/anlegen');
        $form = $crawler->selectButton('add_new_case')->form();
        $client->submit($form, array('form[case_id]' => 'Stockholmer Brandanschlag',
                                     'form[beschreibung]' => 'Festplatte des Brandstifters auf Motive untersuchen'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/faelle"));
        
        
        $client->submit($form, array('form[case_id]' => 'Stockholmer Brandanschlag',
                                     'form[beschreibung]' => 'Festplatte des Brandstifters auf Motive untersuchen'));
        
        // Doppelter Eintrag
        
        $crawler = $client->request('POST', 'http://localhost/fall/anlegen');
        $form = $crawler->selectButton('add_new_case')->form();
        $client->submit($form, array('form[case_id]' => 'Stockholmer Brandanschlag',
                                     'form[beschreibung]' => 'Festplatte des Brandstifters auf Motive untersuchen'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $this->assertStringContainsString("case_id_already_used",$client->getResponse()->getContent());
        
        $this->logoutCorrect($client);
    }
    
    private function AddCorrectObject($parameters,$client){
        
        $formname = "add_object";
        $crawler = $client->request('POST', 'http://localhost/objekt/anlegen');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $form = $crawler->selectButton('add.object')->form();

       
        // Filter for post parameters, which cannot be processed on the creation of a 
        $blacklistformarray=array("kategorie","dueDateDisplay");
        foreach ($parameters as $key => $value){

            if(in_array($key, $blacklistformarray)){
                continue;
            }
            $formarray[$formname.'['.$key.']'] = $parameters[$key];
        }
        $formarray[$formname.'[save]'] = '';

        $client->submit($form, $formarray);
        
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parameters['barcode_id']));
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
       
        # TODO: den Test besser verstehen

        // Test, ob Eintrag vom Objekt in der Uebersicht vorhanden ist!
        $crawler = $client->request('POST', '/objekte');
        $query = $crawler->filter("tr:contains('".$parameters['barcode_id']."')")->text();
        $this->assertStringContainsString($parameters['barcode_id'],$query);
        $this->assertStringContainsString($parameters['name'],$query);
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$parameters['barcode_id']);
        
        # TODO: den Test besser verstehen

        $query = $crawler->filter("#currentstatus")->text();
        
        
        foreach(array("verwendung","name","kategorie") as $index){
            if($parameters[$index] != ""){
                $this->assertStringContainsString($parameters[$index],$query);
            }    
        }
        
        // TODO:  DRINGEND VERSTAENDLICHER MACHEN

        // Datentraegerspezifische Informationen speichern
        $query = $crawler->filter("#hddinfo");
        

        
        foreach(array("bauart",
                      "hersteller",
                      "formfaktor",
                      "modell",
                      "sn",
                      "pn",
                      "anschluss") as $index){
            if($parameters[$index] != ""){
                $this->assertStringContainsString($parameters[$index],$query->text());
            }    
        }
        
        // Groesse kann auf 2 Arten beschrieben werden, in diesem Test wird
        // eine die Normierte Loesung genommen
        if($parameters['groesse'] != "" && $parameters['groessealt'] == ""){
            $this->assertStringContainsString($parameters['groesse'],$query->text());
        }
        elseif ($parameters['groesse'] == "" && $parameters['groessealt'] != "") {
            $this->assertStringContainsString($parameters['groessealt'],$query->text());
        }
        elseif ($parameters['groesse'] != "" && $parameters['groessealt'] != "") {
            $this->assertStringContainsString($parameters['groessealt'],$query->text());
        }
    }
    
    
    
    // Asservat eintragen
    public function testAddCorrectObject1()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        
        $parameters['barcode_id']   = "DTAS00001";
        $parameters['name']         = "(TEST)Hitachi Festplatte";
        $parameters['verwendung']   = "(TEST)Sind gelöschte Beweise drauf";
        $parameters['kategorie_id'] = "0";
        $parameters['kategorie']    = "category.exhibit";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    
    
    // Ausruestung eintragen
    public function testAddCorrectObject2()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        
        $parameters['barcode_id']   = "DTHW00007";
        $parameters['name']         = "(TEST)Thinkpad e330";
        $parameters['verwendung']   = "(TEST)Ediscovery";
        $parameters['kategorie_id'] = "1";
        $parameters['kategorie']    = "category.equipment";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    
    
    
    
    // Datentraeger eintragen
    public function testAddCorrectObject3()
    {
         $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['barcode_id']   = "DTHD00021";
        $parameters['name']         = "(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte";
        $parameters['verwendung']   = "(TEST)Wird für Ein Asservat benötigt";
        $parameters['kategorie_id'] = "3";
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    
     // Objekt ohne verwendungszweck eintragen
    public function testAddCorrectObject4()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['barcode_id']   = "DTHW00001";
        $parameters['name']         = "(TEST)Encase Koffer mit speziffischen Inhalt";
        $parameters['verwendung']   = "";
        $parameters['kategorie_id'] = "1";
        $parameters['kategorie']    = "category.equipment";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    
   
    // Datentraeger eintragen, mit Spezifischen Daten
    public function testAddCorrectObject5()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
         
        $parameters['barcode_id']   = "DTHD00022";
        $parameters['name']         = "(TEST)Toshiba 500GB";
        $parameters['verwendung']   = "(TEST)Austauschplatte Für den Server";
        $parameters['kategorie_id'] = "3";
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "3,5";
        $parameters['groesse']      = "500";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "Modell 1";
        $parameters['hersteller']   = "Toshiba";
        $parameters['sn']           = "89437809756B";
        $parameters['pn']           = "GHII9";
        $parameters['anschluss']    = "SATA";
        
        $this->AddCorrectObject($parameters,$client);
                       
        $this->logoutCorrect($client);
    }
    
    
    
    // Datentraeger eintragen, mit Spezifischen Daten, alternative Groessenangabe
    public function testAddCorrectObject6()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['barcode_id']   = "DTHD00023";
        $parameters['name']         = "(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte";
        $parameters['verwendung']   = "(TEST)Notfallplatte für Forensikkoffer";
        $parameters['kategorie_id'] = "3";
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "extern";
        $parameters['formfaktor']   = "2,5";
        $parameters['groesse']      = "1000";
        $parameters['groessealt']   = "2000";
        $parameters['modell']       = "T00JH9II";
        $parameters['hersteller']   = "Toshiba";
        $parameters['sn']           = "89437809756C";
        $parameters['pn']           = "GHII9";
        $parameters['anschluss']    = "USB";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    
    
     // Behaelter eintragen
    public function testAddCorrectObject7()
    {
         $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['barcode_id']   = "DTHW00002";
        $parameters['name']         = "(TEST)Schrank";
        $parameters['verwendung']   = "(TEST)Wird zum Lagern von Asservaten gebraucht";
        $parameters['kategorie_id'] = "2";
        $parameters['kategorie']    = "category.container";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
      // Behaelter eintragen
    public function testAddCorrectObject8()
    {
         $client = $this->loginWithCorrectCredentials("üser3","test");
        
        $parameters['barcode_id']   = "DTHW00003";
        $parameters['name']         = "(TEST)Papierbox";
        $parameters['verwendung']   = "(TEST)Wird zum Lagern von HDDs gebraucht";
        $parameters['kategorie_id'] = "2";
        $parameters['kategorie']    = "category.container";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    
    
      // Behaelter eintragen
    public function testAddCorrectObject9()
    {
         $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['barcode_id']   = "DTHW00004";
        $parameters['name']         = "(TEST)Peli Case";
        $parameters['verwendung']   = "(TEST)Für Mobilen Einsatz";
        $parameters['kategorie_id'] = "2";
        $parameters['kategorie']    = "category.container";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
     // Behaelter eintragen
    public function testAddCorrectObject10()
    {
         $client = $this->loginWithCorrectCredentials("üser3","test");
        
         
        $parameters['barcode_id']   = "DTHW00005";
        $parameters['name']         = "(TEST)Pappkarton";
        $parameters['verwendung']   = "(TEST)Zwecks Dringlichkeit in das System eingetragen, "
                                        . "nicht im Regen stehen lassen";
        $parameters['kategorie_id'] = "2";
        $parameters['kategorie']    = "category.container";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    // Asservat eintragen
    public function testAddCorrectObject11()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        
        $parameters['barcode_id']   = "DTAS00002";
        $parameters['name']         = "(TEST)Selbstbau Rechner i7 mit Nvidia GTX 970 SLI";
        $parameters['verwendung']   = "(TEST)Eine remote Bitcoinsoftware wurde installiert und "
                                      . "auf ein unbekannte Konto gemint";
        $parameters['kategorie_id'] = "0";
        $parameters['kategorie']    = "category.exhibit";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    // Asservat eintragen
    public function testAddCorrectObject12()
    {
        $client = $this->loginWithCorrectCredentials("üser3","test");
        
        
        $parameters['barcode_id']   = "DTAS00003";
        $parameters['name']         = "(TEST)NAS Server QNAP Server";
        $parameters['verwendung']   = "(TEST)Es wurden Spuren von KiPo Material gefunden";
        $parameters['kategorie_id'] = "0";
        $parameters['kategorie']    = "category.exhibit";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    // Datentraeger eintragen, mit Spezifischen Daten, alternative Groessenangabe
    public function testAddCorrectObject13()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $parameters['barcode_id']   = "DTHD00020";
        $parameters['name']         = "(TEST)WD 256 GB 3.5 Zoll externe Intern";
        $parameters['verwendung']   = "(TEST)Gefunden aus einem älteren Rechner";
        $parameters['kategorie_id'] = "3";
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "3,5";
        $parameters['groesse']      = "250";
        $parameters['groessealt']   = "2000";
        $parameters['modell']       = "WD4AU0078";
        $parameters['hersteller']   = "WD";
        $parameters['sn']           = "6777886546";
        $parameters['pn']           = "KlllU";
        $parameters['anschluss']    = "SATA";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
	 // Datentraeger eintragen, mit Spezifischen Daten
    public function testAddCorrectObject14()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
         
        $parameters['barcode_id']   = "DTHD00024";
        $parameters['name']         = "(TEST)Hitachi Ultrastar 1TB";
        $parameters['verwendung']   = "";
        $parameters['kategorie_id'] = "3";
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "3,5";
        $parameters['groesse']      = "1000";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "K900";
        $parameters['hersteller']   = "Hitachi";
        $parameters['sn']           = "7765398176";
        $parameters['pn']           = "ABCDFG";
        $parameters['anschluss']    = "SATA";
        
        $this->AddCorrectObject($parameters,$client);
                       
        $this->logoutCorrect($client);
    }
	
	 // Datentraeger eintragen, mit Spezifischen Daten
    public function testAddCorrectObject15()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
         
        $parameters['barcode_id']   = "DTHD00025";
        $parameters['name']         = "(TEST)Hitachi Ultrastar 2TB";
        $parameters['verwendung']   = "";
        $parameters['kategorie_id'] = "3";
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "3,5";
        $parameters['groesse']      = "2000";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "K900";
        $parameters['hersteller']   = "Hitachi";
        $parameters['sn']           = "3234512322";
        $parameters['pn']           = "GFEDCA";
        $parameters['anschluss']    = "SATA";
        
        $this->AddCorrectObject($parameters,$client);
                       
        $this->logoutCorrect($client);
    }
    
    
     // Festplattenasservat eintragen, mit Spezifischen Daten
    public function testAddCorrectObject16()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
         
        $parameters['barcode_id']   = "DTAS00004";
        $parameters['name']         = "(TEST)Intel SSD 430 256GB";
        $parameters['verwendung']   = "";
        $parameters['kategorie_id'] = "5";
        $parameters['kategorie']    = "category.exhibit.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "2,5";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "256";
        $parameters['modell']       = "430";
        $parameters['hersteller']   = "Intel";
        $parameters['sn']           = "3344556677";
        $parameters['pn']           = "JUHGFDGHK";
        $parameters['anschluss']    = "SATA";
        
        $this->AddCorrectObject($parameters,$client);
                       
        $this->logoutCorrect($client);
    }
    
    // Festplattenasservat eintragen, mit Spezifischen Daten
    public function testAddCorrectObject17()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
         
        $parameters['barcode_id']   = "DTAS00005";
        $parameters['name']         = "(TEST)Hitachi 20 GB hdd";
        $parameters['verwendung']   = "Befinden sich verschüsselte Daten";
        $parameters['kategorie_id'] = "5";
        $parameters['kategorie']    = "category.exhibit.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "3,5";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "20";
        $parameters['modell']       = "oldware";
        $parameters['hersteller']   = "Hitachi";
        $parameters['sn']           = "123321123";
        $parameters['pn']           = "UJHTNMLOI";
        $parameters['anschluss']    = "ATA";
        
        $this->AddCorrectObject($parameters,$client);
                       
        $this->logoutCorrect($client);
    }
    
    // Behaelter eintragen
    public function testAddCorrectObject18()
    {
         $client = $this->loginWithCorrectCredentials("üser3","test");
        
         
        $parameters['barcode_id']   = "DTHW00006";
        $parameters['name']         = "(TEST)Werkzeugregal";
        $parameters['verwendung']   = "(TEST)Von einem Schwedischen Versandhandel besorgt";
        $parameters['kategorie_id'] = "2";
        $parameters['kategorie']    = "category.container";
        $parameters['bauart']       = "";
        $parameters['formfaktor']   = "";
        $parameters['groesse']      = "";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        
        $this->AddCorrectObject($parameters,$client);
        
        $this->logoutCorrect($client);
    }
    
    
    
    
    // Wenn der Prafix nicht stimmt (DTAS*****).
    public function testAddIncorrectObjectWrongBarcodePrafix()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DKHA00089";
        $name       = "(TEST) Manipulierter Post request 1";
        $verwendung = "(TEST) soll Schaden verursachen";
        $kategorie_id = "0"; // 0: Asservat, 
                             // 1:Ausruestung, 
                             // 2: Behaelter, 
                             // 3: Datentraeger
        
        $crawler = $client->request('POST', 'objekt/anlegen');
        $form = $crawler->selectButton('add.object')->form();
        $client->submit($form, array('add_object[barcode_id]' => $barcode_id,
                                     'add_object[name]' => $name,
                                     'add_object[verwendung]' => $verwendung,
                                     'add_object[kategorie_id]' => $kategorie_id));
        
        // Bei falschen Barcode soll der Nutzer auf der Eintragungsseite 
        // verbleiben
        $this->assertFalse($client->getResponse()->isRedirect("/objekte")); 
        $this->assertStringContainsString("barcode.validation.failed",$client->getResponse()->getContent());
                
        $this->logoutCorrect($client);
    }
    
    

   
    
    
    
    // Objekt ohne verwendungszweck eintragen
    public function testAddDuplicatedObject()
    {
        
        //
        // KNP Sortierung muss deaktiviert werden, da es sonst zu Fehlern von 
        // durch andere Seiten kommt (f in falluebersicht)
        //
        unset($_GET['sort']);
        $client = $this->loginWithCorrectCredentials("user","test");
         
        $barcode_id = "DTHW00009";
        $name       = "(TEST)Lenovo im Doppelpack";
        $verwendung = "Ein Objekt sollte nicht 2 mal vorkommen";
        $kategorie_id = "1"; // 0: Asservat, 
                             // 1:Ausruestung, 
                             // 2: Behaelter, 
                             // 3: Datentraeger
        $kategorie = "category.equipment";
        
        $crawler = $client->request('POST', '/objekt/anlegen');
        $form = $crawler->selectButton('add.object')->form();
        $client->submit($form, array('add_object[barcode_id]' => $barcode_id,
                                     'add_object[name]' => $name,
                                     'add_object[verwendung]' => $verwendung,
                                     'add_object[kategorie_id]' => $kategorie_id));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        // Test, ob Eintrag vom Objekt in der Uebersicht vorhanden ist!
        $crawler = $client->request('POST', '/objekte');
       
        $query = $crawler->filter("tr:contains('".$barcode_id."')")->text();
        
        $this->assertStringContainsString($barcode_id,$query);
        $this->assertStringContainsString($name,$query);
        
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        
        $query = $crawler->filter("#currentstatus")->text();
        
        
       
        $this->assertStringContainsString($name,$query);
        $this->assertStringContainsString($kategorie,$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        //$time = new \DateTime('now');
        //$this->assertStringContainsString($time->format('d.m.y H:i'),$query); 
        
        
        // Versuch, das Gleiche Objekt nochmal anzulegen
        $crawler = $client->request('POST', '/objekt/anlegen');
        
        $form = $crawler->selectButton('add.object')->form();
        $client->submit($form, array('add_object[barcode_id]' => $barcode_id,
                                     'add_object[name]' => $name,
                                     'add_object[verwendung]' => $verwendung,
                                     'add_object[kategorie_id]' => $kategorie_id,
                                     'add_object[save]' => ""));
        
        $this->assertEquals("200",$client->getResponse()->getStatusCode());
        $this->assertFalse($client->getResponse()->isRedirect("/objekte"));
        $this->assertStringContainsString("barcode.already.exists.in.database",$client->getResponse()->getContent());
        
        
        $this->logoutCorrect($client);
    }
    
    
    
    // Wenn das Eingetragene Objekt einen zu kurzen Barcode hat,
    // beispiel zeichen geloescht.
     public function testAddIncorrectObjectWrongBarcodeLenghtToShort()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
                       // Nur 7 stellig, muessen 9 sein
        $barcode_id = "DTAS002";
        $name       = "(TEST)Falsch eingebenenes Objekt";
        $verwendung = "(TEST)Verwendungszweck ist nicht nötigt";
        $kategorie_id = "0"; // 0: Asservat, 
                             // 1: Ausruestung, 
                             // 2: Behaelter, 
                             // 3: Datentraeger
        
        $crawler = $client->request('POST', 'objekt/anlegen');
        $form = $crawler->selectButton('add.object')->form();
        $client->submit($form, array('add_object[barcode_id]' => $barcode_id,
                                     'add_object[name]' => $name,
                                     'add_object[verwendung]' => $verwendung,
                                     'add_object[kategorie_id]' => $kategorie_id,
                                     'add_object[save]' => ""));
        
        // Bei falschen Barcode soll die eintragung nicht weiter erfolgen
        $this->assertFalse($client->getResponse()->isRedirect("/objekte"));
        
        $this->assertStringContainsString("barcode.validation.failed",$client->getResponse()->getContent());
          
        $this->logoutCorrect($client);
    }
    
    // Wenn der Barcode zu lang ist, beispiel wenn vertippt
    public function testAddIncorrectObjectWrongBarcodeLenghtToLong()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
                       // Nur 10 stellig, muessen 9 sein
        $barcode_id = "DTAS002355";
        $name       = "(TEST)Falsch eingebenenes Objekt";
        $verwendung = "(TEST)Verwendungszweck ist nicht nötigt";
        $kategorie_id = "0"; // 0: Asservat, 
                             // 1: Ausruestung, 
                             // 2: Behaelter, 
                             // 3: Datentraeger
        
        $crawler = $client->request('POST', 'objekt/anlegen');
        $form = $crawler->selectButton('add.object')->form();
        $client->submit($form, array('add_object[barcode_id]' => $barcode_id,
                                     'add_object[name]' => $name,
                                     'add_object[verwendung]' => $verwendung,
                                     'add_object[kategorie_id]' => $kategorie_id,
                                     'add_object[save]' => ""));
        
        // Bei falschen Barcode soll die eintragung nicht weiter erfolgen
        $this->assertFalse($client->getResponse()->isRedirect("/objekte")); 
        
        $this->assertStringContainsString("barcode.validation.failed",$client->getResponse()->getContent());
          
        $this->logoutCorrect($client);
    }
    
    
    // Wenn der Barcode zu lang ist, beispiel wenn vertippt
    public function testAddIncorrectObjectWrongBarcodeCategory()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
                       
        $barcode_id = "DTAS00235";
        $name       = "(TEST)Das Asservat soll als Ausrüstung kategoriesiert werden";
        $verwendung = "(TEST)Es wäre nicht gut, wenn dies so umgesetzt wird";
        $kategorie_id = "1"; // 0: Asservat, 
                             // 1: Ausruestung, 
                             // 2: Behaelter, 
                             // 3: Datentraeger
        
        $crawler = $client->request('POST', 'objekt/anlegen');
        $form = $crawler->selectButton('add.object')->form();
        $client->submit($form, array('add_object[barcode_id]' => $barcode_id,
                                     'add_object[name]' => $name,
                                     'add_object[verwendung]' => $verwendung,
                                     'add_object[kategorie_id]' => $kategorie_id,
                                     'add_object[save]' => ""));
        
        // Bei falschen Barcode soll die eintragung nicht weiter erfolgen
        $this->assertFalse($client->getResponse()->isRedirect("/objekte")); 
        
        $this->assertStringContainsString("object.is.not.saved",$client->getResponse()->getContent());
          
        $this->logoutCorrect($client);
    }
    
    
    
    
    // Wenn bei der Eintragung Objektes kein Name eingegeben wird
    public function testAddIncorrectObjectEmptyName()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
                       // Nur 10 stellig, muessen 9 sein
        $barcode_id = "DTHD00004";
        $name       = "";
        $verwendung = "(TEST)Verwendungszweck ist nicht nötigt";
        $kategorie_id = "3"; // 0: Asservat, 
                             // 1: Ausruestung, 
                             // 2: Behaelter, 
                             // 3: Datentraeger
        
        $crawler = $client->request('POST', 'objekt/anlegen');
        $form = $crawler->selectButton('add.object')->form();
        $client->submit($form, array('add_object[barcode_id]' => $barcode_id,
                                     'add_object[name]' => $name,
                                     'add_object[verwendung]' => $verwendung,
                                     'add_object[kategorie_id]' => $kategorie_id,
                                     'add_object[save]' => ""));
        
        // Bei falschen Barcode soll die eintragung nicht weiter erfolgen
        $this->assertFalse($client->getResponse()->isRedirect("/objekte")); 
        $this->logoutCorrect($client);
    }
    
    
    
     // Datentraeger eintragen, mit Spezifischen Daten, alternative Groessenangabe
    public function testAddIncorrectObjectHDDWrongBarcode()
    {
         $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTAK00023";
        $name       = "(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte";
        $verwendung = "(TEST)Notfallplatte für Forensikkoffer";
        $kategorie_id = "3"; // 0: Asservat, 
                             // 1:Ausruestung, 
                             // 2: Behaelter, 
                             // 3: Datentraeger
        $kategorie = "Datenträger";
        
        $bauart = "extern";
        $formfaktor = "2,5";
        $groesse = "2000";
        $groessealt = "2200";
        $anschluss = "USB";
        $hersteller = "Toshiba";
        $modell = "T00JH9II";
        $sn = "89437809756C";
        $pn = "GHII9";
        
        
        
        $crawler = $client->request('POST', 'objekt/anlegen');
        $form = $crawler->selectButton('add.object')->form();
        $client->submit($form, array('add_object[barcode_id]' => $barcode_id,
                                     'add_object[name]' => $name,
                                     'add_object[verwendung]' => $verwendung,
                                     'add_object[kategorie_id]' => $kategorie_id,
                                     'add_object[bauart]' => $bauart,
                                     'add_object[formfaktor]' => $formfaktor,
                                     'add_object[groesse]' => $groesse,
                                     'add_object[groessealt]' => $groessealt,
                                     'add_object[anschluss]' => $anschluss,
                                     'add_object[hersteller]' => $hersteller,
                                     'add_object[modell]' => $modell,
                                     'add_object[sn]' => $sn,
                                     'add_object[pn]' => $pn,
                                     'add_object[save]' => ""
            ));
        
        
        $this->assertFalse($client->getResponse()->isRedirect("/objekte"));                
        $this->logoutCorrect($client);
    }
    
 
    
    
    // Datentraeger wird genullt
    public function testNullCorrectObject()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHD00021";
        $name       = "(TEST)Toshiba 2 TB 2.5 Zoll externe Festplatte";
        $verwendung = "(TEST)Wird für Ein Asservat benötigt";
        $kategorie_id = \App\Controller\helper::KATEGORIE_DATENTRAEGER; 
                          
        $kategorie = "category.hdd";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/nullen');
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Aus Testzwecken wird dieses Objekt genullt deklariert'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        // Test, ob Eintrag vom Objekt in der Uebersicht vorhanden ist!
        $crawler = $client->request('POST',"/objekte");
        
        
        
        
        $query = $crawler->filter("tr:contains('".$barcode_id."')")->text();
        $this->assertStringContainsString($barcode_id,$query);
        $this->assertStringContainsString($name,$query);
        
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        
        
        
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString($name,$query);
        $this->assertStringContainsString($kategorie,$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("status.cleaned",$query);  // <-- Ist das Objekt genullt?
       
        $this->logoutCorrect($client);      
    }
    
    
    
    
    
    // Ausruestung soll genullt werden
    public function testNullInCorrectObject()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHW00001";
        $name       = "(TEST)Encase Koffer mit speziffischen Inhalt";
        $verwendung = "";
        $kategorie_id = \App\Controller\helper::KATEGORIE_AUSRUESTUNG; // 0: Asservat, 
                             // 1:Ausruestung, 
                             // 2: Behaelter, 
                             // 3: Datentraeger
        $kategorie = "category.equipment";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/nullen');
        
        
        // Es ist OK, dass man hier zurueckkommt, allerdings darf keine Aenderung passiert sein
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        // Test, ob Eintrag vom Objekt in der Uebersicht vorhanden ist!
        $crawler = $client->request('POST',"/objekte");
        
        $query = $crawler->filter("tr:contains('".$barcode_id."')")->text();
        $this->assertStringContainsString($barcode_id,$query);
        $this->assertStringContainsString($name,$query);
        
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString($name,$query);
        $this->assertStringContainsString($kategorie,$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringNotContainsStringIgnoringCase("status.cleaned",$query);  // <-- Ist die Ausruestung genullt?
  
        $this->logoutCorrect($client);
    }
    
    
    
    // Datentraeger wird vernichtet
    public function testDestroyedObject()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHD00022";
        
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/vernichtet');
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Aus Testzwecken wird dieses Objekt zerstört deklariert'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        // Test, ob Eintrag vom Objekt in der Uebersicht vorhanden ist!
        $crawler = $client->request('POST',"/objekte");
        
        
        
        
        $query = $crawler->filter("tr:contains('".$barcode_id."')")->text();
        $this->assertStringContainsString($barcode_id,$query);        
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("status.destroyed",$query);  // <-- Ist das Objekt zerstoert?  
        $this->logoutCorrect($client);      
    }
    
    
    // Datentraeger wird vernichtet
    public function testLostObject()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHD00023";
        
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/verloren');
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Aus Testzwecken wird dieses Objekt verloren deklariert'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        // Test, ob Eintrag vom Objekt in der Uebersicht vorhanden ist!
        $crawler = $client->request('POST',"/objekte");
        
        
        
        
        $query = $crawler->filter("tr:contains('".$barcode_id."')")->text();
        $this->assertStringContainsString($barcode_id,$query);        
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("status.lost",$query);  // <-- Ist das Objekt verloren?  
        $this->logoutCorrect($client);      
    }
    
    
    
    // Versuch, nicht mehr veraenderbare Objekte zu veraendern
    public function testChangeNotChangeableObjects()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        // Wurde vernichtet im vorherigen Test
        $barcode_id = "DTHD00022";
                
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/verwenden');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/editieren');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in/DTHW00002');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/in/fall/XIVv2/hinzufuegen');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        // Wurde verloren im vorherigen Test
        $barcode_id = "DTHD00023";
                
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/verwenden');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/editieren');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        // nicht fertig
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in/DTHW00002');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/in/fall/XIVv2/hinzufuegen');
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        $this->logoutCorrect($client);
    }
    
    // Ausrüstung wird reserviert 
    public function testReserveCorrectObject1()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHW00001";
        $name       = "(TEST)Encase Koffer mit speziffischen Inhalt";
        $verwendung = "Es wird für einen Penetrationstests temporär reserviert";
        $kategorie_id = \App\Controller\helper::KATEGORIE_AUSRUESTUNG; 
                          
        $kategorie = "category.equipment";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/reservieren');
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Es wird für einen Penetrationstests temporär reserviert'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        // Test, ob Eintrag vom Objekt in der Uebersicht vorhanden ist!
        $crawler = $client->request('POST',"/objekte");
        
        
        
        
        $query = $crawler->filter("tr:contains('".$barcode_id."')")->text();
        $this->assertStringContainsString($barcode_id,$query);
        $this->assertStringContainsString($name,$query);
        
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        
        
        
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString($name,$query);
        $this->assertStringContainsString($kategorie,$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("status.reserved",$query);  // <-- Ist das Objekt genullt?
       
        $this->logoutCorrect($client);      
    }
    
    

    // Datentraeger wird in den Schrank gelegt
    public function testStoreObject()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHW00009";
        // Schrank
        $store_barcode_id= "DTHW00002";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in/'.$store_barcode_id);
        
       
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Aus Testzwecken wird dieses Objekt in den Schrank gelegt'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
                     
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("DTHW00002 | (TEST)Schrank",$query);  // <-- Ist das Objekt dem Schrank zugeordnet?  
        $this->logoutCorrect($client);      
    }
    
    
    // Schrank darf nicht sich selbst beinhalten
    public function testIncorrectStoreSelfContainer()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        // Schrank
        $store_barcode_id= "DTHW00002";
        
        $crawler = $client->request('POST', '/objekt/'.$store_barcode_id.'/einlegen/in/'.$store_barcode_id);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$store_barcode_id));
    }
    
    
    // Bereits eingelagertes Objekt darf nicht wieder einlegbar sein
    public function testIncorrectStoreStoredObject()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        // Ist bereits im Schrank
         $barcode_id = "DTHW00009";
        // Schrank
        $store_barcode_id= "DTHW00002";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in/'.$store_barcode_id);
        
       
         
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        
    }
    
    
    
    // Behaelter duerfen sich nicht gegenseitig einlagern
    public function testIncorrectCrossRelationContainer()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHW00003";
        // Schrank
        $store_barcode_id= "DTHW00002";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in/'.$store_barcode_id);
        
       
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Aus Testzwecken wird dieses Objekt in den Schrank gelegt'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
                     
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("DTHW00002 | (TEST)Schrank",$query);  // <-- Ist das Objekt dem Schrank zugeordnet?  
        
        
        $crawler = $client->request('POST', '/objekt/'.$store_barcode_id.'/einlegen/in/'.$barcode_id);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$store_barcode_id));
        
        $this->logoutCorrect($client);      
    }
    
    // Behaelter duerfen sich nicht gegenseitig einlagern, erweitert
    //      DTHW00002
    //      |       \
    //      |     DTHW00003
    //      |          \
    //      -------> DTHW00004
    public function testIncorrectCrossRelationContainerExtended()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHW00004";
        // Schrank
        $store_barcode_id= "DTHW00003";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in/'.$store_barcode_id);
        
       
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Aus Testzwecken wird dieses Objekt in den Schrank gelegt'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
                     
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("DTHW00003 | (TEST)Papierbox",$query);  // <-- Ist das Objekt dem Schrank zugeordnet?  
        
        
        $barcode_id = "DTHW00002";
        $store_barcode_id= "DTHW00004";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in/'.$store_barcode_id);
        
        
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        $this->logoutCorrect($client);      
    }
    
    
    // Festplatte in zerstoerten Behaelter legen
    public function testIncorrectDestroyedContainer()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        // Hitashi Festplatte
        $barcode_id = "DTAS00001";
        // Pappkarton
        $store_barcode_id= "DTHW00005";
        
        
        // Pappkarton wird vernichtet
        $crawler = $client->request('POST', '/objekt/'.$store_barcode_id.'/vernichtet');
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Wurde im Regen stehen gelassen, ist aufgeweicht'));
        
        
        $client->request('POST', '/objekt/'.$barcode_id.'/einlegen/in/'.$store_barcode_id);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
                     
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringNotContainsStringIgnoringCase("DTHW00005 | (TEST)Pappkarton",$query);  // <-- Ist das Objekt dem Schrank zugeordnet?  
        
        $this->logoutCorrect($client);      
    }
    
    
    // Manipulation der Url, um Integritaet zu stören
    public function testIncorrectSwappedStoredObjekts()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
       
        // Peli Case
        $barcode_id = "DTHW00001";
        // Werkzeugregal
        $store_barcode_id= "DTHW00006";
        
        $client->request('POST', '/objekt/'.$store_barcode_id.'/einlegen/in/'.$barcode_id);
        
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$store_barcode_id));
          
        $this->logoutCorrect($client);      
    }
    
    
    
    // Lenovo Laptop wird einem Fall hinzugefuegt
    public function testAddtoCaseObject()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $barcode_id = "DTHW00009";
        
        $case_id= "XIVv2";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/in/fall/'.$case_id.'/hinzufuegen');
        
       
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Musste beim Fall hinzugezogen werden'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
                     
        
        // Test, ob Detailansicht in Ordnung ist
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        $query = $crawler->filter("#currentstatus")->text();
        
       
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString($case_id,$query);  // <-- Ist das Objekt dem Fall zugeordnet ?  
        $this->logoutCorrect($client);      
    }

    
    // Auxillian Method against boilercode, execute a search for Objects and
    // count the number of results
    public function searchobjects($searchstring, $numberofqueries)
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $crawler = $client->request('POST', 'http://localhost/objekte');
        $form = $crawler->selectButton('Suchen')->form();
        $crawler = $client->submit($form, array('form[suchwort]' => $searchstring,
                                     'form[anzahleintraege]' => '1000'));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals($numberofqueries, $crawler->filter("tbody tr")->count()); 
        
    }  
    
    public function testsearchobject1(){
        $this->searchobjects('name:"lenovo"', 1);
    }
    
    public function testsearchobject2(){
        $this->searchobjects("hu:'Üser3'", 2);
    }
    public function testsearchobject3(){
        $this->searchobjects("mu:'Üser3'", 2);
    }
    
    public function testsearchobject4(){
        $this->searchobjects('mu:"Üser3"', 2);
    }
    
    public function testsearchobject5(){
        $this->searchobjects('mr:false', 18);
    }
    
    public function testsearchobject6(){
        $this->searchobjects('mr:true', 1);
    }
    


    
    // Auxillian Method against boilercode for mass object modification
    public function updateMassObjects($barcodes,
                                        $newstatus,
                                        $contextobject,
                                        $description,
                                        $isCorrect
                                        )
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        
        if($contextobject != null){
            // An AJAX call has to be made, to store objects or add to case
            $crawler = $client->request("POST",
                                        "/objekte/aendern/", 
                                        array(
                                            "action_choose" => array(
                                                "searchbox" => $contextobject,
                                                "newstatus" => $newstatus
                                            )
                                        ),
                                        array(), 
                                        array(
                                            'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ));

            $form = $crawler->selectButton('Select objects')->form();

            
            // dueDate has to be set manually, cause of the indirect submit of the page
            $crawler = $client->submit($form, array('action_choose[newdescription]' => $description,
                                         'action_choose[newstatus]' => $newstatus ,
                                         'action_choose[contextthings]' => $contextobject,
                                         'action_choose[dueDate]' => '2018-10-12T10:00:00'));
            
           
        }
        else{
            $crawler = $client->request("GET","/objekte/aendern/");
            
            $form = $crawler->selectButton('Select objects')->form();
            
            $crawler = $client->submit($form, array('action_choose[newdescription]' => $description,
                                         'action_choose[newstatus]' => $newstatus ));
            
            
        }
        
        

        $this->assertTrue($client->getResponse()->isRedirect("/objekte/aendern/in"));
        
        

        $crawler = $client->followRedirect();
        
        $form = $crawler->selectButton('label.do.action')->form();

        
        $formdata = $form->getPhpValues();
        
        
        foreach($barcodes as $key=>$barcode){
            $formdata['form']['objects'][$key] = $barcode;
            
        }
        
        $crawler = $client->request($form->getMethod(), 
                                    $form->getUri(),
                                    $formdata,
                                    $form->getPhpFiles());
        
       
        
        
        // Is it a positive or negativ test?
        if($isCorrect == true){
            $this->assertTrue($client->getResponse()->isRedirect("/objekte"));
        }
        else{
            $this->assertFalse($client->getResponse()->isRedirect("/objekte"));
        }
        
        
        $this->logoutCorrect($client);      
    }
    
     
    public function testmassupdate1(){
        $newstatus = helper::STATUS_IN_EINEM_BEHAELTER_GELEGT;
        $description = "(TEST) temporäre Verwahrung";
        
        $this->updateMassObjects(array("DTAS00001"),
                                        $newstatus,
                                        "DTHW00004",
                                        $description,
                                        true);    
                
    }
    // Festplatten nullen
    public function testmassupdate2(){
        $newstatus = helper::STATUS_GENULLT;
        $description = "(TEST) Vorsichtshalber genullt, um Probleme zu vermeiden";
        $this->updateMassObjects(array("DTHD00020","DTHD00024"),
                                        $newstatus , 
                                        null,
                                        $description,
                                        true);    
                
    }
    // Mehrere Objekte in einem Behälter legen
    public function testmassupdate3(){
        $newstatus = helper::STATUS_IN_EINEM_BEHAELTER_GELEGT;
        $description = "(TEST) Zusammenstellung für einen Forensikeinsatz";
        
        $this->updateMassObjects(array("DTHD00021","DTHW00007"),
                                        $newstatus,
                                        "DTHW00004",
                                        $description,
                                        true);    
                
    }
    
    // Mehrere Behaelter und andere Objekte nutzen
    public function testmassupdate4(){
        $newstatus = helper::STATUS_IN_VERWENDUNG;
        $description = "(TEST) Analyse einer Festplatte";
        
        
        $this->updateMassObjects(array("DTHW00004","DTHW00007"),
                                        $newstatus,
                                        null,
                                        $description,
                                        true);    
                
    }
    
    // Versuch, Asservate zu nullen
    public function testmassupdateIncorrect1(){
        $newstatus = helper::STATUS_GENULLT;
        $description = "(TEST) Asservate sollten nicht genullt werden können";
        $this->updateMassObjects(array("DTAS00001","DTAS00002","DTAS00004"),
                                        $newstatus , 
                                        null,
                                        $description,
                                        false);    
                
    }

    
    
    // Bereits genullte Festplatten nullen
    public function testmassupdateIncorrect2(){
        $newstatus = helper::STATUS_GENULLT;
        $description = "(TEST) Festplatten müssen gekaputt genullt werden";
        $this->updateMassObjects(array("DTHD00020","DTHD00024"),
                                        $newstatus , 
                                        null,
                                        $description,
                                        false);    
                
    }
    
    // neben existieren Objekten auch ungültige ändern
    public function testmassupdateIncorrect3(){
        $newstatus = helper::STATUS_IN_VERWENDUNG;
        $description = "(TEST) DTHD99999 existiert natürlich";
        $this->updateMassObjects(array("DTHD00020","DTHD00024","DTHD99999"),
                                        $newstatus , 
                                        null,
                                        $description,
                                        false);    
                
    }
    
    
    
    // Image Datentraegerasservat wird einer HDD hinzugefuegt
    public function testAddImageToHDD()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        
        $exhibit_hdd_barcode_id = "DTAS00005";
        
        $hdd_barcode_id= "DTHD00025";
        
        
        $crawler = $client->request('POST', '/objekt/'.$hdd_barcode_id.'/Asservatenimage/speichern/von/'.$exhibit_hdd_barcode_id."/0");
        
       
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => 'Eine Bitweise Kopie erstellt. Beim Kopieren wurden jedoch fehlerhafte Sektoren übersprungen'));
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$hdd_barcode_id));
        
        $this->logoutCorrect($client);      
    }
    
    
    // Das Gleiche Image vom Vortest hinzufuegen
    public function testAddDoubleImageToHDD()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        $exhibit_hdd_barcode_id = "DTAS00005";
        $hdd_barcode_id= "DTHD00025";
        
        $client->request('POST', '/objekt/'.$hdd_barcode_id.'/Asservatenimage/speichern/von/'.$exhibit_hdd_barcode_id."/0");
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$hdd_barcode_id));
         
        $this->logoutCorrect($client);      
    }
    
    
     // Quell und Zieladresse werden vertauscht
    public function testIncorrectSwapTargetSourceSaveImage()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        
        
        $exhibit_hdd_barcode_id = "DTAS00004";
        
        $hdd_barcode_id= "DTHD00025";
        
        
        $client->request('POST', '/objekt/'.$exhibit_hdd_barcode_id.'/Asservatenimage/speichern/von/'.$hdd_barcode_id."/0");
        
        // returnid -> /objekt/0/Asservatenimage/speichern/von/1
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$exhibit_hdd_barcode_id));
        $this->logoutCorrect($client);      
    }
    
    
    public function doCorrectAction($client,
                                    $barcode_id,
                                    $contextthing,
                                    $newstatus,
                                    $verwendung){
        
        // TODO: contextthings for actions like put in container
        $simpleactionList = array(
            helper::STATUS_GENULLT => "nullen",
            helper::STATUS_VERNICHTET => "vernichtet",
            helper::STATUS_AN_PERSON_UEBERGEBEN=> "uebergeben",
            helper::STATUS_RESERVIERT => "reservieren",
            helper::STATUS_VERLOREN => "verloren",
            helper::STATUS_RESERVIERUNG_AUFGEHOBEN=> "reservierung/aufheben",
            helper::VSTATUS_NEUTRALISIERT => "neutralisieren"
        );
        
        $complexactionList = array(
            helper::STATUS_EINEM_FALL_HINZUGEFUEGT => array("in/fall","/hinzufuegen"),
            helper::STATUS_IN_EINEM_BEHAELTER_GELEGT => array("einlegen/in",""),
        );
        
        
        
        
        
        if(array_key_exists($newstatus, $simpleactionList)){
            $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/'.$simpleactionList[$newstatus]);
        }
        else{
            $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/'.
                                     $complexactionList[$newstatus][0].'/'.
                                                         $contextthing.
                                     $complexactionList[$newstatus][1]);
        }
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, array('form[verwendung]' => $verwendung));
        
        $this->assertNotEquals(500, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));
        
        
        // Check, if action is done correctly
        $crawler = $client->request('POST', '/objekt/'.$barcode_id);
        
        $query = $crawler->filter("#currentstatus")->text();
        
        $this->assertStringContainsString($verwendung,$query);
        
        switch($newstatus){
            case helper::VSTATUS_NEUTRALISIERT:
                // Test, if one of the status is present
                $pattern = "/status\.removed\.from\.case|status\.pulled\.out\.of\.container|status\.cleaned/";
                $this->assertMatchesRegularExpression($pattern, $query);
                break;
            default:
                $status = array_search($newstatus, helper::$statusToId);
                $this->assertStringContainsString($status,$query);  
                break;
        }
        
       
                
    }
    
    
    public function testNewtralizeHDD(){
        $verwendung = "Bei der letzten Formatierung wurde nicht richtig drauf geachtet, dass HPA deaktiviert wurde";
        $client = $this->loginWithCorrectCredentials("user","test");
        $this->doCorrectAction($client,"DTHD00021", null, helper::STATUS_GENULLT, $verwendung);
        
        $verwendung = "hinzugezogen für weiteres Image";
        $this->doCorrectAction($client,"DTHD00021", "78/98", helper::STATUS_EINEM_FALL_HINZUGEFUEGT, $verwendung);
        
        // Object is already in container 
        
        $verwendung = "war unnötig gewesen die Platte mitzunehmen";
        $this->doCorrectAction($client, "DTHD00021", null, helper::VSTATUS_NEUTRALISIERT, $verwendung);
        
    }
    
    public function testNewtralizeHDD2(){
        $client = $this->loginWithCorrectCredentials("user","test");
        $verwendung = "Bei der letzten Formatierung wurde nicht richtig drauf geachtet, dass HPA deaktiviert wurde";
        $this->doCorrectAction($client,"DTHD00025", null, helper::STATUS_GENULLT, $verwendung);
        
        $verwendung = "hinzugezogen für weiteres Image";
        $this->doCorrectAction($client,"DTHD00025", "XIVv2", helper::STATUS_EINEM_FALL_HINZUGEFUEGT, $verwendung);
        
        // Object is already in container 
        
        $verwendung = "Kurzzeitig abgelegt, da Tresor voll ist";
        $this->doCorrectAction($client,"DTHD00025", "DTHW00006", helper::STATUS_IN_EINEM_BEHAELTER_GELEGT, $verwendung);
        
    }
    
    public function testInCorrectNewtralizeObject(){
        $client = $this->loginWithCorrectCredentials("user","test");
        
        
        $barcode_id = "DTHW00002";
        $verwendung = "Eine Ausrüstung kann nicht neutralisiert werden";
        
        $crawler = $client->request('POST', '/objekt/'.$barcode_id.'/neutralisieren');
                
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$barcode_id));       
        
    }

    // Als nicht registrierter Nutzer soll dieser bei jedem Aufruf auf die Login
    // Seite verwiesen werden
    public function testNonAnonymousActions(){
         $client = $this->loginWithCorrectCredentials("user","test");
        
        $crawler = $client->request('GET', '/objekt/DTHD00020');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

	$crawler = $client->request('GET', '/objekt/DTHD00020/neutralisieren');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

	$crawler = $client->request('GET', '/objekt/DTHD00020/Asservatenimage/speichern/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
       
	$crawler = $client->request('GET', '/objekt/DTHD00020/vernichtet');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

	$crawler = $client->request('GET', '/objekt/DTHD00020/verwenden');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
	$crawler = $client->request('GET', '/objekt/DTHD00020/reservieren');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

	$crawler = $client->request('GET', '/objekt/DTHD00020/einlegen/in');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

	$crawler = $client->request('GET', '/objekt/DTHD00020/in/fall');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

	$crawler = $client->request('GET', '/objekt/DTHD00020/editieren');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

	$crawler = $client->request('GET', '/objekt/DTHD00020/upload');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

// ----------------------------------------------------------------------

    public function testCorrectLoggingHistorie1()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        

        $parameters['barcode_id']   = "DTHD00500";
        $parameters['name']         = "(TEST_Historie) Archiv Tape 1";
        $parameters['verwendung']   = "(TEST_Historie) Wird zum Archivieren von Falldaten verwendet";
        $parameters['kategorie_id'] = \App\Entity\Objekt::KATEGORIE_DATENTRAEGER;
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "RDX";
        $parameters['groesse']      = "250";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        $parameters['dueDate']    = "2024-10-15T11:00:10";
        $parameters['dueDateDisplay']    = "15.10.24 11:00";
        
        $this->AddCorrectObject($parameters,$client);


        $newduedate='2024-10-15T12:00:09';
        $newduedatedisplay='15.10.24 12:00';
        $newverwendung='Fuer den Test der Historie wurde das Tape ueberschrieben';
        
        $crawler = $client->request('POST', '/objekt/'.$parameters['barcode_id'].'/nullen');
        
        $formarray=array('form[verwendung]' => $newverwendung,
        'form[dueDate]' => $newduedate
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parameters['barcode_id']));     
        
        $crawler = $client->request('POST', '/objekt/'.$parameters['barcode_id']);
        
        
        // Check of current status
        $query = $crawler->filter("#currentstatus")->text();
        $this->assertStringContainsString($parameters['name'] ,$query);
        $this->assertStringContainsString($parameters['kategorie'],$query);
        $this->assertStringContainsString($newduedatedisplay,$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("status.cleaned",$query);  // <-- is object nulled?
       
        // Get list of history
        $HistoryEntries = $crawler->filter('#history')->filter("tr");

        $this->assertTrue((count($HistoryEntries) != 0), "Count of history entries are zero");


        // Select the first history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-1)->text();
        $this->assertStringContainsString($parameters['verwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the history entry");
        $this->assertStringContainsString($parameters['dueDateDisplay'],
                                            $query,
                                            "It seems, that the web application did not log the history entry");

        $this->assertStringContainsString("status.added",
                                            $query,
                                            "It seems, that the web application did not log the history entry");

        $this->logoutCorrect($client);      
    }
    


    public function testCorrectLoggingHistorie2()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        

        $parameters['barcode_id']   = "DTHD00510";
        $parameters['name']         = "(TEST_Historie) Archiv Tape 2";
        $parameters['verwendung']   = "(TEST_Historie) Wird zum Archivieren von Falldaten verwendet";
        $parameters['kategorie_id'] = \App\Entity\Objekt::KATEGORIE_DATENTRAEGER;
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "RDX";
        $parameters['groesse']      = "250";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        $parameters['dueDate']    = "2024-10-13T11:00:10";
        $parameters['dueDateDisplay']    = "13.10.24 11:00";
        
        $this->AddCorrectObject($parameters,$client);



        $parameters['barcode_id']   = "DTHD00501";
        $parameters['name']         = "(TEST_Historie) Archiv Tape 2";
        $parameters['verwendung']   = "(TEST_Historie) Wird zum Archivieren von Falldaten verwendet";
        $parameters['kategorie_id'] = \App\Entity\Objekt::KATEGORIE_DATENTRAEGER;
        $parameters['kategorie']    = "category.hdd";
        $parameters['bauart']       = "intern";
        $parameters['formfaktor']   = "RDX";
        $parameters['groesse']      = "250";
        $parameters['groessealt']   = "";
        $parameters['modell']       = "";
        $parameters['hersteller']   = "";
        $parameters['sn']           = "";
        $parameters['pn']           = "";
        $parameters['anschluss']    = "";
        $parameters['dueDate']    = "2024-10-13T11:00:10";
        $parameters['dueDateDisplay']    = "13.10.24 11:00";
        
        $this->AddCorrectObject($parameters,$client);

        
        $actioninput=array();
        $actioninput[0]=array();
        
        // Alter object first time
        $actioninput[0]['newduedate'] ='2024-10-13T14:00:09';
        $actioninput[0]['newduedatedisplay']='13.10.24 14:00';
        $actioninput[0]['newverwendung']='Speicherung von Temporaeren verschuesselten Daten';
        $actioninput[0]['newstatus']='status.used';

        $crawler = $client->request('POST', '/objekt/'.$parameters['barcode_id'].'/verwenden');
        
        $formarray=array('form[verwendung]' => $actioninput[0]['newverwendung'],
        'form[dueDate]' => $actioninput[0]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parameters['barcode_id']));     
        

        // Alter object second time
        $actioninput[1]['newduedate']='2024-10-14T14:00:09';
        $actioninput[1]['newduedatedisplay']='14.10.24 14:00';
        $actioninput[1]['newverwendung']='Fuer den Test der Historie wurde das Tape ueberschrieben';
        $actioninput[1]['newstatus']='status.cleaned';
        
        $crawler = $client->request('POST', '/objekt/'.$parameters['barcode_id'].'/nullen');
        
        $formarray=array('form[verwendung]' => $actioninput[1]['newverwendung'],
        'form[dueDate]' => $actioninput[1]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parameters['barcode_id']));     
        

        /*
            Expectations:
            Current Status: Nulled
            Previous Status: Used
            Last Status:  Added
        */

        $crawler = $client->request('POST', '/objekt/'.$parameters['barcode_id']);
        
        
        // Check of current status
        $query = $crawler->filter("#currentstatus")->text();
        $this->assertStringContainsString($parameters['name'] ,$query);
        $this->assertStringContainsString($parameters['kategorie'],$query);
        $this->assertStringContainsString($actioninput[1]['newduedatedisplay'],$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString("status.cleaned",$query);  // <-- is object nulled?
       
        // Get list of history
        $HistoryEntries = $crawler->filter('#history')->filter("tr");

        $this->assertTrue((count($HistoryEntries) != 0), "Count of history entries are zero");


        // Check creation/first history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-1)->text();
        $this->assertStringContainsString($parameters['verwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the creation of the object");
        $this->assertStringContainsString($parameters['dueDateDisplay'],
                                            $query,
                                            "It seems, that the web application did not log the creation of the object");

        $this->assertStringContainsString("status.added",
                                            $query,
                                            "It seems, that the web application did not log the creation of the object");


        // Check used action/second history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-2)->text();
        $this->assertStringContainsString($actioninput[0]['newverwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the alteration of the object");
        $this->assertStringContainsString($actioninput[0]['newduedatedisplay'],
                                            $query,
                                            "It seems, that the web application did not log the alteration of the object");

        $this->assertStringContainsString($actioninput[0]['newstatus'],
                                            $query,
                                            "It seems, that the web application did not log the alteration of the object");


        $this->logoutCorrect($client);      
    }




    public function testCorrectLoggingHistorie3()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        

        $parametersHDD['barcode_id']   = "DTHD00502";
        $parametersHDD['name']         = "(TEST_Historie) Archiv Tape 3";
        $parametersHDD['verwendung']   = "(TEST_Historie) Wird zum Archivieren von Falldaten verwendet";
        $parametersHDD['kategorie_id'] = \App\Entity\Objekt::KATEGORIE_DATENTRAEGER;
        $parametersHDD['kategorie']    = "category.hdd";
        $parametersHDD['bauart']       = "intern";
        $parametersHDD['formfaktor']   = "RDX";
        $parametersHDD['groesse']      = "250";
        $parametersHDD['groessealt']   = "";
        $parametersHDD['modell']       = "";
        $parametersHDD['hersteller']   = "";
        $parametersHDD['sn']           = "";
        $parametersHDD['pn']           = "";
        $parametersHDD['anschluss']    = "";
        $parametersHDD['dueDate']    = "2024-10-13T11:00:10";
        $parametersHDD['dueDateDisplay']    = "13.10.24 11:00";
        
        $this->AddCorrectObject($parametersHDD,$client);

       


        $parametersContainer['barcode_id']   = "DTHW00500";
        $parametersContainer['name']         = "(TEST_Historie) Tapedose";
        $parametersContainer['verwendung']   = "(TEST_Historie) Verwendung zur Lagerung von Tapes im RDX Format";
        $parametersContainer['kategorie_id'] = \App\Entity\Objekt::KATEGORIE_BEHAELTER;
        $parametersContainer['kategorie']    = "category.container";
        $parametersContainer['bauart']       = "";
        $parametersContainer['formfaktor']   = "";
        $parametersContainer['groesse']      = "";
        $parametersContainer['groessealt']   = "";
        $parametersContainer['modell']       = "";
        $parametersContainer['hersteller']   = "";
        $parametersContainer['sn']           = "";
        $parametersContainer['pn']           = "";
        $parametersContainer['anschluss']    = "";
        $parametersContainer['dueDate']    = "2024-10-13T12:00:10";
        $parametersContainer['dueDateDisplay']    = "13.10.24 12:00";
        
       
        $this->AddCorrectObject($parametersContainer,$client);

        // Note: Actions are only performed on the HDD!

        $actioninput=array();
        $actioninput[0]=array();
        
        // Alter object first time
        $actioninput[0]['newduedate'] ='2024-10-13T13:15:09';
        $actioninput[0]['newduedatedisplay']='13.10.24 13:15';
        $actioninput[0]['newverwendung']='Speicherung von Temporaeren verschuesselten Daten';
        $actioninput[0]['newstatus']='status.used';

        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/verwenden');
    


        $formarray=array('form[verwendung]' => $actioninput[0]['newverwendung'],
                         'form[dueDate]' => $actioninput[0]['newduedate']);
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parametersHDD['barcode_id']));     
        

        // Alter object second time/add to Container
        $actioninput[1]['newduedate']='2024-10-14T14:00:09';
        $actioninput[1]['newduedatedisplay']='14.10.24 14:00';
        $actioninput[1]['newverwendung']='Fuer den Test der Historie wurde das Tape in die Box gelegt';
        $actioninput[1]['newstatus']='status.stored.in.container';
        
        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/einlegen/in/'.$parametersContainer['barcode_id']);
        

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $formarray=array('form[verwendung]' => $actioninput[1]['newverwendung'],
                        'form[dueDate]' => $actioninput[1]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        

        // Alter object third time/remove from Container
        $actioninput[2]['newduedate']='2024-10-14T14:10:09';
        $actioninput[2]['newduedatedisplay']='14.10.24 14:10';
        $actioninput[2]['newverwendung']='Fuer den Test der Historie das Tape wieder aus der Box geholt';
        $actioninput[2]['newstatus']='status.pulled.out.of.container';
        
        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/entnehmen');
        
        //echo $crawler->html();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $formarray=array('form[verwendung]' => $actioninput[2]['newverwendung'],
                        'form[dueDate]' => $actioninput[2]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);



        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parametersHDD['barcode_id']));     
        

        /*
            Expectations:
            Current Status: Pulled out of container
            Previous Status: Stored in a Container
            Previous Status: Used
            Last Status:  Added
        */

        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id']);
        
        
        // Check of current status
        $query = $crawler->filter("#currentstatus")->text();
        $this->assertStringContainsString($parametersHDD['name'] ,$query);
        $this->assertStringContainsString($parametersHDD['kategorie'],$query);
        $this->assertStringContainsString($actioninput[2]['newduedatedisplay'],$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString($actioninput[2]['newstatus'],$query);  // <-- is object pulled out of container?
       
        // Get list of history
        $HistoryEntries = $crawler->filter('#history')->filter("tr");

        $this->assertTrue((count($HistoryEntries) != 0), "Count of history entries are zero");


        // Check creation/first history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-1)->text();
        $this->assertStringContainsString($parametersHDD['verwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the creation of the object");
        $this->assertStringContainsString($parametersHDD['dueDateDisplay'],
                                            $query,
                                            "It seems, that the web application did not log the creation of the object");

        $this->assertStringContainsString("status.added",
                                            $query,
                                            "It seems, that the web application did not log the creation of the object");


        // Check used action/second history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-2)->text();
        $this->assertStringContainsString($actioninput[0]['newverwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the alteration of the object");
        $this->assertStringContainsString($actioninput[0]['newduedatedisplay'],
                                            $query,
                                            "It seems, that the web application did not log the alteration of the object");

        $this->assertStringContainsString($actioninput[0]['newstatus'],
                                            $query,
                                            "It seems, that the web application did not log the alteration of the object");

        // Check Stored if Container action/third history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-3)->text();
        $this->assertStringContainsString($actioninput[1]['newverwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the storage of the object to a container");
        $this->assertStringContainsString($actioninput[1]['newduedatedisplay'],
                                            $query,
                                            "It seems, that the web application did not log the storage of the object to a container");

        $this->assertStringContainsString($actioninput[1]['newstatus'],
                                            $query,
                                            "It seems, that the web application did not log the storage of the object to a container");



        $this->logoutCorrect($client);      
    }









    public function testCorrectLoggingHistorie4()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        

        $parametersHDD['barcode_id']   = "DTHD00503";
        $parametersHDD['name']         = "(TEST_Historie) Archiv Tape 3";
        $parametersHDD['verwendung']   = "(TEST_Historie) Wird zum Archivieren in einen dedizierten Fall gelegt";
        $parametersHDD['kategorie_id'] = \App\Entity\Objekt::KATEGORIE_DATENTRAEGER;
        $parametersHDD['kategorie']    = "category.hdd";
        $parametersHDD['bauart']       = "intern";
        $parametersHDD['formfaktor']   = "RDX";
        $parametersHDD['groesse']      = "250";
        $parametersHDD['groessealt']   = "";
        $parametersHDD['modell']       = "";
        $parametersHDD['hersteller']   = "";
        $parametersHDD['sn']           = "";
        $parametersHDD['pn']           = "";
        $parametersHDD['anschluss']    = "";
        $parametersHDD['dueDate']    = "2024-10-13T11:00:10";
        $parametersHDD['dueDateDisplay']    = "13.10.24 11:00";
        
        $this->AddCorrectObject($parametersHDD,$client);

    
        $parametersCase['id']= "AKT89";
        $parametersCase['desc'] = "(TEST)Manipulation Archivdaten";
        
        $this->AddCorrectCase($parametersCase, $client);
        
        

        // Note: Actions are only performed on the HDD!

        $actioninput=array();
        $actioninput[0]=array();
        
        // Alter object first time
        $actioninput[0]['newduedate'] ='2024-10-13T13:00:09';
        $actioninput[0]['newduedatedisplay']='13.10.24 13:00';
        $actioninput[0]['newverwendung']='Sichtung der auf dem Datentraeger verfuegbaren Daten';
        $actioninput[0]['newstatus']='status.used';

        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/verwenden');
    


        $formarray=array('form[verwendung]' => $actioninput[0]['newverwendung'],
                         'form[dueDate]' => $actioninput[0]['newduedate']);
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parametersHDD['barcode_id']));     
        

        // Alter object second time/add to Case
        $actioninput[1]['newduedate']='2024-10-13T16:00:09';
        $actioninput[1]['newduedatedisplay']='13.10.24 16:00';
        $actioninput[1]['newverwendung']='Fuer den Test der Historie wurde das Tape in den Fall eingebunden';
        $actioninput[1]['newstatus']='status.added.to.case';
    

        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/in/fall/'.$parametersCase['id'].'/hinzufuegen');
        $form = $crawler->selectButton('label.do.action')->form();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $formarray=array('form[verwendung]' => $actioninput[1]['newverwendung'],
                        'form[dueDate]' => $actioninput[1]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        
        

        // Alter object third time/remove from Container
        $actioninput[2]['newduedate']='2024-10-14T11:10:09';
        $actioninput[2]['newduedatedisplay']='14.10.24 11:10';
        $actioninput[2]['newverwendung']='Fuer den Test der Historie das Tape wieder aus dem Fall entfernt';
        $actioninput[2]['newstatus']='status.removed.from.case';
        
        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/aus/Fall/entfernen');
        
        //echo $crawler->html();


        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $formarray=array('form[verwendung]' => $actioninput[2]['newverwendung'],
                        'form[dueDate]' => $actioninput[2]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);



        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parametersHDD['barcode_id']));     
        

        /*
            Expectations:
            Current Status: Removed of the Case
            Previous Status: Added to Case
            Previous Status: Used
            Last Status:  Added
        */

        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id']);
        
        
        // Check of current status
        $query = $crawler->filter("#currentstatus")->text();
        $this->assertStringContainsString($parametersHDD['name'] ,$query);
        $this->assertStringContainsString($parametersHDD['kategorie'],$query);
        $this->assertStringContainsString($actioninput[2]['newduedatedisplay'],$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString($actioninput[2]['newstatus'],$query);  // <-- is object removed from case?
       
        // Get list of history
        $HistoryEntries = $crawler->filter('#history')->filter("tr");

        $this->assertTrue((count($HistoryEntries) != 0), "Count of history entries are zero");


        // Check creation/first history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-1)->text();
        $this->assertStringContainsString($parametersHDD['verwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the creation of the object");
        $this->assertStringContainsString($parametersHDD['dueDateDisplay'],
                                            $query,
                                            "It seems, that the web application did not log the creation of the object");

        $this->assertStringContainsString("status.added",
                                            $query,
                                            "It seems, that the web application did not log the creation of the object");


        // Check second history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-2)->text();
        $this->assertStringContainsString($actioninput[0]['newverwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the alteration of the object");
        $this->assertStringContainsString($actioninput[0]['newduedatedisplay'],
                                            $query,
                                            "It seems, that the web application did not log the alteration of the object");

        $this->assertStringContainsString($actioninput[0]['newstatus'],
                                            $query,
                                            "It seems, that the web application did not log the alteration of the object");

        // Check third history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-3)->text();
        $this->assertStringContainsString($actioninput[1]['newverwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the adding of the object to a case");
        $this->assertStringContainsString($actioninput[1]['newduedatedisplay'],
                                            $query,
                                            "It seems, that the web application did not log the adding of the object to a case");

        $this->assertStringContainsString($actioninput[1]['newstatus'],
                                            $query,
                                            "It seems, that the web application did not log the adding of the object to a case");



        $this->logoutCorrect($client);      
    }










    public function testCorrectLoggingHistorie5()
    {
        $client = $this->loginWithCorrectCredentials("user","test");
        

        $parametersHDD['barcode_id']   = "DTHD00504";
        $parametersHDD['name']         = "(TEST_Historie) Archiv Tape 4";
        $parametersHDD['verwendung']   = "(TEST_Historie) Wird zum Speichern von Asservatsdaten verwendet";
        $parametersHDD['kategorie_id'] = \App\Entity\Objekt::KATEGORIE_DATENTRAEGER;
        $parametersHDD['kategorie']    = "category.hdd";
        $parametersHDD['bauart']       = "intern";
        $parametersHDD['formfaktor']   = "RDX";
        $parametersHDD['groesse']      = "250";
        $parametersHDD['groessealt']   = "";
        $parametersHDD['modell']       = "";
        $parametersHDD['hersteller']   = "";
        $parametersHDD['sn']           = "";
        $parametersHDD['pn']           = "";
        $parametersHDD['anschluss']    = "";
        $parametersHDD['dueDate']    = "2024-10-12T11:00:10";
        $parametersHDD['dueDateDisplay']    = "12.10.24 11:00";
        
        $this->AddCorrectObject($parametersHDD,$client);

       


        $parametersExhibit['barcode_id']   = "DTAS00500";
        $parametersExhibit['name']         = "(TEST_Historie) SSD 64GB S/N: JJIU889JK";
        $parametersExhibit['verwendung']   = "(TEST_Historie) Ausgebaut aus System des Beschuldigten";
        $parametersExhibit['kategorie_id'] = \App\Entity\Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER;
        $parametersExhibit['kategorie']    = "category.exhibit.hdd";
        $parametersExhibit['bauart']       = "intern";
        $parametersExhibit['formfaktor']   = "2,5";
        $parametersExhibit['groesse']      = "64";
        $parametersExhibit['groessealt']   = "";
        $parametersExhibit['modell']       = "";
        $parametersExhibit['hersteller']   = "";
        $parametersExhibit['sn']           = "JJIU889JK";
        $parametersExhibit['pn']           = "";
        $parametersExhibit['anschluss']    = "SATA";
        $parametersExhibit['dueDate']    = "2024-10-11T12:00:10";
        $parametersExhibit['dueDateDisplay']    = "11.10.24 12:00";
        
       
        $this->AddCorrectObject($parametersExhibit,$client);

        // Note: Actions are only performed on the HDD!

        $actioninput=array();
        $actioninput[0]=array();
        
        // Alter object first time
        $actioninput[0]['newduedate'] ='2024-10-13T13:13:09';
        $actioninput[0]['newduedatedisplay']='13.10.24 13:13';
        $actioninput[0]['newverwendung']='Lagerung des DD-Images auf den Datentraeger unverschuesselt';
        $actioninput[0]['newstatus']='status.saved.image';

        $crawler = $client->request('POST','/objekt/'.$parametersHDD['barcode_id'].'/Asservatenimage/speichern/von/'.$parametersExhibit['barcode_id'].'/0');
    


        $formarray=array('form[verwendung]' => $actioninput[0]['newverwendung'],
                         'form[dueDate]' => $actioninput[0]['newduedate']);
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        
        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parametersHDD['barcode_id']));     
        

        // Alter object second time/use the object
        $actioninput[1]['newduedate']='2024-10-14T14:10:09';
        $actioninput[1]['newduedatedisplay']='14.10.24 14:10';
        $actioninput[1]['newverwendung']='Fuer den Test der Historie das Tape das aktiv verwenden';
        $actioninput[1]['newstatus']='status.used';
        
        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/verwenden');
        

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $formarray=array('form[verwendung]' => $actioninput[1]['newverwendung'],
                        'form[dueDate]' => $actioninput[1]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);
        

        // Alter object third time/null the HDD to erase the contained image
        $actioninput[2]['newduedate']='2024-10-13T13:30:09';
        $actioninput[2]['newduedatedisplay']='13.10.24 13:30';
        $actioninput[2]['newverwendung']='Fuer den Test der Historie wurde das Tape genullt';
        $actioninput[2]['newstatus']='status.cleaned';
        
        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/nullen');
        
        
        //echo $crawler->html();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $formarray=array('form[verwendung]' => $actioninput[2]['newverwendung'],
                        'form[dueDate]' => $actioninput[2]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);



        // Alter object forth time/use the object
        $actioninput[3]['newduedate']='2024-10-14T14:12:09';
        $actioninput[3]['newduedatedisplay']='14.10.24 14:12';
        $actioninput[3]['newverwendung']='Fuer den Test der Historie das Tape das zweite Mal aktiv verwenden';
        $actioninput[3]['newstatus']='status.used';
        
        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id'].'/verwenden');
        
        //echo $crawler->html();

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        
        $formarray=array('form[verwendung]' => $actioninput[3]['newverwendung'],
                        'form[dueDate]' => $actioninput[3]['newduedate']
        );
        
        $form = $crawler->selectButton('label.do.action')->form();
        $client->submit($form, $formarray);



        $this->assertTrue($client->getResponse()->isRedirect("/objekt/".$parametersHDD['barcode_id']));     
        

        /*
            Expectations:
            Current Status: Object used
            Previous Status: Object nulled -> Image of DTAS00500 NOT avaiable
            Previous Status: Object used  -> Image of DTAS00500 avaiable
            Previous Status: Image creation -> Image of DTAS00500 avaiable
            Last Status:  Added
        */

        $crawler = $client->request('POST', '/objekt/'.$parametersHDD['barcode_id']);
        
        
        // Check of current status
        $query = $crawler->filter("#currentstatus")->text();
        $this->assertStringContainsString($parametersHDD['name'] ,$query);
        $this->assertStringContainsString($parametersHDD['kategorie'],$query);
        $this->assertStringContainsString($actioninput[3]['newduedatedisplay'],$query);
        $this->assertStringContainsString('desc.luser Testuser',$query);
        $this->assertStringContainsString($actioninput[3]['newstatus'],$query);  // <-- is object used?
       
        // Get list of history
        $HistoryEntries = $crawler->filter('#history')->filter("tr");

        $this->assertTrue((count($HistoryEntries) != 0), "Count of history entries are zero");


        // Check creation/first history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-1)->text();
        $this->assertStringContainsString($parametersHDD['verwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the creation of the object");
        $this->assertStringContainsString($parametersHDD['dueDateDisplay'],
                                            $query,
                                            "It seems, that the web application did not log the creation of the object");

        $this->assertStringContainsString("status.added",
                                            $query,
                                            "It seems, that the web application did not log the creation of the object");


        // Check second history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-2)->text();
        $this->assertStringContainsString($actioninput[0]['newverwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the creation of the DD-Image of DTAS00500");
        $this->assertStringContainsString($actioninput[0]['newduedatedisplay'],
                                            $query,
                                            "It seems, that the web application did not log the creation of the DD-Image of DTAS00500");

        $this->assertStringContainsString($actioninput[0]['newstatus'],
                                            $query,
                                            "It seems, that the web application did not log the creation of the DD-Image of DTAS00500");

        // Check third history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-3)->text();
        $this->assertStringContainsString($actioninput[1]['newverwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log usage of the Object");
        $this->assertStringContainsString($actioninput[1]['newduedatedisplay'],
                                            $query,
                                            "It seems, that the web application did not log usage of the Object");

        $this->assertStringContainsString($actioninput[1]['newstatus'],
                                            $query,
                                            "It seems, that the web application did not log usage of the Object");
        
        // Check, if the target exhibit is listed on this specific history entry
        $this->assertStringContainsString('DTAS00500',
                                            $query,
                                            "It seems, that the web application did not log the storage of the DD-imaged exhibit");

        // Check fourth history entry
        $query = $HistoryEntries->eq(count($HistoryEntries)-4)->text();
        $this->assertStringContainsString($actioninput[2]['newverwendung'] ,
                                            $query, 
                                            "It seems, that the web application did not log the nulling of the object");
        $this->assertStringContainsString($actioninput[2]['newduedatedisplay'],
                                            $query,
                                            "It seems, that the web application did not log the nulling of the object");

        $this->assertStringContainsString($actioninput[2]['newstatus'],
                                            $query,
                                            "It seems, that the web application did not log the nulling of the object");
        
        // Check, if the target exhibit is NOT listed on this specific history entry, due to the nulling process
        $this->assertStringNotContainsString('DTAS00500',
                                            $query,
                                            "It seems, that the web application did log, that the DD-imaged DTAS00500 is still available, which should be cleared due nulling");



        $this->logoutCorrect($client);      
    }





    //TODO: Tests der Historieneintraege nach der editierung eines Objekts
    // EDIT reset form ansehen
    
    // TODO: Tests zum Upload von Bildern hinzufuegen
    // TODO: Tests zum Word-Export hinzufuegen
    // TODO: Faelle mit Objekten pruefen
    
}
