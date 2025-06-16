<?php

namespace App\Tests\_support;

use App\Repository\NutzerRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Ben Brooksnieder
 */
abstract class BaseWebTestCase extends WebTestCase
{
    use ResetDatabase;
    protected function loginAdmin(KernelBrowser $client): KernelBrowser
    {
        $userRepository = static::getContainer()->get(NutzerRepository::class);
        $client->loginUser($userRepository->findOneByUsername('admin'));

        return $client;
    }

    protected function loginUser(KernelBrowser $client): KernelBrowser
    {
        $userRepository = static::getContainer()->get(NutzerRepository::class);
        $client->loginUser($userRepository->findOneByUsername('user'));

        return $client;
    }

    /**
     * Asserts that given criteria matches a database entry for given repository.
     * @param string $repository
     * @param array $matchCriteria
     * @param int|null $amount
     * @return void
     */
    protected function seeInDatabase(string $repository, array $matchCriteria, ?int $amount = null) {
        $repo = static::getContainer()->get($repository);
        if(null === $amount)
            $this->assertNotEmpty($repo->findBy($matchCriteria), "Failed asserting that a $repository matching record exists in database.");
        else
            $this->assertCount($amount, $repo->findBy($matchCriteria), "Failed asserting that $amount $repository matching records exists in database.");

    }
    
    /**
     * Asserts that given criteria does not match any database entry for given repository.
     * @param string $repository
     * @param array $matchCriteria
     * @return void
     */
    protected function dontSeeInDatabase(string $repository, array $matchCriteria) {
        $repo = static::getContainer()->get($repository);
        $this->assertEmpty($repo->findBy($matchCriteria), "Failed asserting that no $repository matching records exist in database.");
    }
}
