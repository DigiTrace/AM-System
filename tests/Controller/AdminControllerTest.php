<?php

namespace App\Tests\Controller;

use App\Repository\NutzerRepository;
use App\Tests\_support\BaseWebTestCase;
use App\Tests\Factory\NutzerFactory;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @author Ben Brooksnieder
 */
class AdminControllerTest extends BaseWebTestCase
{
    //
    // ================ DATA PROVIDERS ================
    //

    public function urlProvider()
    {
        yield ['/admin/nutzeruebersicht'];
        yield ['/admin/user/enable'];
        yield ['/admin/user/subscription'];
        yield ['/admin/adduser'];
    }

    public function userSummaryProvider()
    {
        yield [[]];
        yield [[
            'enabled' => true,
            'notifyCaseCreation' => true,
            'roles' => ['ROLE_ADMIN'],
        ]];
        yield [[
            'enabled' => false,
            'notifyCaseCreation' => false,
            'roles' => ['ROLE_USER'],
        ]];
    }

    public function setUserActionProvider()
    {
        yield [false, false];
        yield [false, true];
        yield [true, false];
        yield [true, true];
    }

    public function invalidUserProvider()
    {
        yield [[
            'username' => 'Admin', // already in use
            'fullname' => 'huhiu',
            'email' => 'what@an.email',
            'plainpassword' => 'password :)',
            'notifyCaseCreation' => '0',
        ]];
        yield [[
            'username' => 'Admin',
            'fullname' => 'user', // already in use
            'email' => 'what@an.email',
            'plainpassword' => 'password :)',
            'notifyCaseCreation' => '0',
        ]];
        yield [[
            'username' => 'Admin',
            'fullname' => 'huhiu',
            'email' => 'user@localhost', // already in use
            'plainpassword' => 'password :)',
            'notifyCaseCreation' => '0',
        ]];
    }

    public function validUserProvider()
    {
        yield [[
            'username' => 'Gottfried',
            'fullname' => 'Gott Fried',
            'email' => 'Gott-fried@email.com',
            'plainpassword' => 'password :)',
            'notifyCaseCreation' => '0',
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
     * @dataProvider urlProvider
     */
    public function testAdminProtectedUrls($url)
    {
        $client = static::createClient();
        $this->loginUser($client)->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @dataProvider userSummaryProvider
     */
    public function testUserSummary($attr)
    {
        // setup
        $factory = NutzerFactory::new();
        $user = $factory->with($attr)->create();

        // do request
        $client = static::createClient();

        // test overview page
        $crawler = $this->assertInOverviewPageCorrect($this->loginAdmin($client), $user);
    }

    /**
     * @dataProvider setUserActionProvider
     */
    public function testSetUserEnableAction($enabled, $set_enabled)
    {
        // setup
        $factory = NutzerFactory::new();
        $client = $this->loginAdmin(static::createClient());
        $csrf = static::getContainer()->get('security.csrf.token_manager');

        $user = $factory->with([
            'enabled' => $enabled,
        ])->create();

        $form_data = [
            'username' => $user->getUsername(),
            'enable' => $set_enabled ? 'on' : 'off',
            '_token' => $csrf->getToken('set_enable_user')->getValue(),
        ];

        // do request
        $client->request('POST', '/admin/user/enable', $form_data);
        $this->assertResponseIsSuccessful();

        $this->seeInDatabase(NutzerRepository::class, [
            'id' => $user->getId(),
            'enabled' => $set_enabled,
        ]);
    }

    /**
     * @dataProvider setUserActionProvider
     */
    public function testSetCaseSubscriptionAction($enabled, $set_enabled)
    {
        // setup
        $factory = NutzerFactory::new();
        $client = $this->loginAdmin(static::createClient());
        $csrf = static::getContainer()->get('security.csrf.token_manager');

        $user = $factory->with([
            'notifyCaseCreation' => $enabled,
        ])->create();

        $form_data = [
            'username' => $user->getUsername(),
            'enable' => $set_enabled ? 'on' : 'off',
            '_token' => $csrf->getToken('set_case_subscription_user')->getValue(),
        ];

        // do request
        $client->request('POST', '/admin/user/subscription', $form_data);
        $this->assertResponseIsSuccessful();

        $this->seeInDatabase(NutzerRepository::class, [
            'id' => $user->getId(),
            'notifyCaseCreation' => $set_enabled,
        ]);
    }

    /**
     * @dataProvider invalidUserProvider
     */
    public function testAddUserInvalid($user)
    {
        // setup
        $client = static::createClient();
        $crawler = $this->loginAdmin($client)->request('GET', '/admin/adduser');

        // make form request
        $form = $crawler->selectButton('form[save]')->form();

        // add objekt parameters
        foreach ($user as $key => $value) {
            $form["form[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('div.alert-danger', 'security.duplicate_user');

        unset($user['plainpassword']);
        $user['notifyCaseCreation'] = ('1' === $user['notifyCaseCreation']);

        // check database for correct entry
        $this->dontSeeInDatabase(NutzerRepository::class, $user);
    }

    /**
     * @depends testAddUserInvalid
     *
     * @dataProvider validUserProvider
     */
    public function testAddUserValid($user)
    {
        // setup
        $client = static::createClient();
        $crawler = $this->loginAdmin($client)->request('GET', '/admin/adduser');

        // make form request
        $form = $crawler->selectButton('form[save]')->form();

        // add objekt parameters
        foreach ($user as $key => $value) {
            $form["form[{$key}]"] = $value ?? '';
        }

        $client->submit($form);
        $this->assertResponseIsSuccessful();

        unset($user['plainpassword']);
        $user['notifyCaseCreation'] = ('1' === $user['notifyCaseCreation']);

        // check database for correct entry
        $this->seeInDatabase(NutzerRepository::class, $user);
    }

    //
    // ================ HELPER METHODS ================
    //

    protected function assertInOverviewPageCorrect($client, $user): Crawler
    {
        if (is_object($user)) {
            $user = [
                'username' => $user->getUsername(),
                'fullname' => $user->getFullname(),
                'email' => $user->getEMail(),
                'enabled' => $user->isEnabled(),
                'notify' => $user->getNotifyCaseCreation(),
                'admin' => in_array('ROLE_ADMIN', $user->getRoles()),
            ];
        }

        $crawler = $client->request('GET', '/admin/nutzeruebersicht');
        $this->assertSelectorTextContains("tr:contains('{$user['username']}')", $user['username']);
        $this->assertSelectorTextContains("tr:contains('{$user['username']}')", $user['fullname']);
        $this->assertSelectorTextContains("tr:contains('{$user['username']}')", $user['email']);
        // test state
        $this->assertSelectorTextContains(
            "span.user_state[data-username='{$user['username']}'][style='display:none;']",
            $user['enabled'] ? 'user.form.state_disabled' : 'user.form.state_enabled'
        );
        // test notify case creation
        $this->assertSelectorTextContains(
            "span.subscription_state[data-username='{$user['username']}'][style='display:none;']",
            $user['notify'] ? 'user.form.state_unsubscribed' : 'user.form.state_subscribed'
        );
        if (!$user['admin']) {
            $this->assertSelectorExists("a.toggle_enable_user[data-username='{$user['username']}']");
        } else {
            $this->assertSelectorNotExists("a.toggle_enable_user[data-username='{$user['username']}']");
        }

        return $crawler;
    }
}
