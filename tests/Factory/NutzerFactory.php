<?php

namespace App\Tests\Factory;

use App\Entity\Nutzer;
use App\Repository\NutzerRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<Nutzer>
 *
 * @method        Nutzer|Proxy                              create(array|callable $attributes = [])
 * @method static Nutzer|Proxy                              createOne(array $attributes = [])
 * @method static Nutzer|Proxy                              find(object|array|mixed $criteria)
 * @method static Nutzer|Proxy                              findOrCreate(array $attributes)
 * @method static Nutzer|Proxy                              first(string $sortedField = 'id')
 * @method static Nutzer|Proxy                              last(string $sortedField = 'id')
 * @method static Nutzer|Proxy                              random(array $attributes = [])
 * @method static Nutzer|Proxy                              randomOrCreate(array $attributes = [])
 * @method static NutzerRepository|ProxyRepositoryDecorator repository()
 * @method static Nutzer[]|Proxy[]                          all()
 * @method static Nutzer[]|Proxy[]                          createMany(int $number, array|callable $attributes = [])
 * @method static Nutzer[]|Proxy[]                          createSequence(iterable|callable $sequence)
 * @method static Nutzer[]|Proxy[]                          findBy(array $attributes)
 * @method static Nutzer[]|Proxy[]                          randomRange(int $min, int $max, array $attributes = [])
 * @method static Nutzer[]|Proxy[]                          randomSet(int $number, array $attributes = [])
 */
final class NutzerFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Nutzer::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'email' => self::faker()->email(),
            'enabled' => self::faker()->boolean(),
            'fullname' => self::faker()->name(),
            'notifyCaseCreation' => self::faker()->boolean(),
            'password' => '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2',
            'roles' => ['ROLE_USER'],
            'username' => self::faker()->name(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Nutzer $nutzer): void {})
        ;
    }

    public function enabled(): self
    {
        return $this->with(['enabled' => true]);
    }

    public function admin(): self
    {
        return $this->with(['roles' => ['ROLE_ADMIN', 'ROLE_USER']]);
    }

    /**
     * Sets password to "test"
     * @return NutzerFactory
     */
    public function testPassword(): self
    {
        // "test" as hashed password
        return $this->with(['password' => '$2y$13$aHIe6aZt8yN7EWSJ7zLEzeed2SntSUaz7YSgp3X2Y2S6zz358Pyv2']);
    }
}
