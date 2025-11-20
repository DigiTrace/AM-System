<?php

namespace App\Tests\Controller;

use App\Repository\NutzerRepository;
use App\Tests\_support\BaseWebTestCase;
use App\Tests\Factory\NutzerFactory;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Ben Brooksnieder
 */
class ProfileControllerTest extends BaseWebTestCase
{
    //
    // ================ DATA PROVIDERS ================
    //

    public function urlProvider()
    {
        yield ['/profil'];
        yield ['/profil/aendern'];
        yield ['/profil/passwort'];
    }

     public function duplicateUserProvider(){
        yield [[
            'username' => 'Admin', // already in use
        ]];
        yield [[
            'fullname' => 'user', // already in use
        ]];
        yield [[
            'email' => 'user@localhost', // already in use
        ]];
    }

    public function changeUserProvider() {
        yield [[
            'username' => 'Steven'
        ]];
        yield [[
            'fullname' => 'MammaMia'
        ]];
        yield [[
            'username' => 'Manfred',
            'fullname' => 'Manni Fred',
            'email' => 'mmanni@fred.de',
            'notifyCaseCreation' => "1",
        ]];
    }

    //
    // ================ TESTS ================
    //

    /**
     * @dataProvider urlProvider
     */
    public function testProtectedUrls($url)
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    /**
     * @dataProvider duplicateUserProvider
     */
    public function testChangeUserInfoFailureDuplicates($attr) {
        // setup
        $factory = NutzerFactory::new();
        $client = static::createClient();

        $user = $factory->with(['enabled' => true])->create();
        $crawler = $client->loginUser($user->_real())->request('GET', '/profil');
        $this->assertResponseIsSuccessful();

        // make form request
        $form = $crawler->selectButton('form[save]')->form();
        foreach ($attr as $key => $value) {
            $form["form[{$key}]"] = $value ?? '';
        }

        // add objekt parameters
        $form["form[id]"] = $user->getId() - 1;
        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-danger', 'user.form.error.duplicate');

        $this->dontSeeInDatabase(NutzerRepository::class, array_merge($attr, ['id' => $user->getId()]));
    }

    /**
     * @dataProvider changeUserProvider
     */
    public function testChangeUserInfoSuccess($attr) {
        // setup
        $factory = NutzerFactory::new();
        $client = static::createClient();

        $user = $factory->with([
            'enabled' => true, 
            'notifyCaseCreation' => false,
        ])->create();
        $crawler = $client->loginUser($user->_real())->request('GET', '/profil');
        $this->assertResponseIsSuccessful();

        // make form request
        $form = $crawler->selectButton('form[save]')->form();
        foreach ($attr as $key => $value) {
            $form["form[{$key}]"] = $value ?? '';
        }

        $client->submit($form);

        $user_data = [
            'username' => $user->getUsername(),
            'fullname' => $user->getFullname(),
            'email' => $user->getEMail(),
            'notifyCaseCreation' => $user->getNotifyCaseCreation(),
        ];

        if (isset($attr['notifyCaseCreation']))
            $attr['notifyCaseCreation'] = ('1' === $attr['notifyCaseCreation']);

        // check database for correct entry
        $this->seeInDatabase(NutzerRepository::class, array_merge($user_data, $attr));
    }


    public function testChangePasswordFailureWrongPassword() {
        // setup
        $factory = NutzerFactory::new();
        $client = static::createClient();

        $password = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2'; // "test"

        $user = $factory->with([
            'enabled' => true,
            'password' => $password,
        ])->create();
        $crawler = $client->loginUser($user->_real())->request('GET', '/profil/passwort');
        $this->assertResponseIsSuccessful();

        // make form request
        $form = $crawler->selectButton('form[save]')->form();
        $form['form[old_password]'] = 'nicht test';
        $form['form[new_password]'] = 'password';
        $form['form[new_password_repeat]'] = 'password';

        $client->submit($form);

        $user_data = [
            'id' => $user->getId(),
            'password' => $password,
        ];

        // check database for correct entry
        $this->seeInDatabase(NutzerRepository::class, $user_data);
    }

    public function testChangePasswordFailurePasswordsDontMatch() {
        // setup
        $factory = NutzerFactory::new();
        $client = static::createClient();

        $password = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2'; // "test"

        $user = $factory->with([
            'enabled' => true,
            'password' => $password,
        ])->create();
        $crawler = $client->loginUser($user->_real())->request('GET', '/profil/passwort');
        $this->assertResponseIsSuccessful();

        // make form request
        $form = $crawler->selectButton('form[save]')->form();
        $form['form[old_password]'] = 'test';
        $form['form[new_password]'] = 'password';
        $form['form[new_password_repeat]'] = 'password2';

        $client->submit($form);

        $user_data = [
            'id' => $user->getId(),
            'password' => $password,
        ];

        // check database for correct entry
        $this->seeInDatabase(NutzerRepository::class, $user_data);
    }

    public function testChangePasswordSuccess() {
        // setup
        $factory = NutzerFactory::new();
        $client = static::createClient();

        $password = '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2'; // "test"

        $user = $factory->with([
            'enabled' => true,
            'password' => $password,
        ])->create();
        $crawler = $client->loginUser($user->_real())->request('GET', '/profil/passwort');
        $this->assertResponseIsSuccessful();

        // make form request
        $form = $crawler->selectButton('form[save]')->form();
        $form['form[old_password]'] = 'test';
        $form['form[new_password]'] = 'password';
        $form['form[new_password_repeat]'] = 'password';

        $client->submit($form);

        $user_data = [
            'id' => $user->getId(),
            'password' => $password,
        ];

        // check database for correct entry
        $this->dontSeeInDatabase(NutzerRepository::class, $user_data);
    }
}
