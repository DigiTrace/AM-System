<?php

namespace App\Tests\Factory;

use App\Entity\Datentraeger;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use function Zenstruck\Foundry\lazy;

/**
 * @extends PersistentProxyObjectFactory<Datentraeger>
 *
 * @method        Datentraeger|Proxy                        create(array|callable $attributes = [])
 * @method static Datentraeger|Proxy                        createOne(array $attributes = [])
 * @method static Datentraeger|Proxy                        find(object|array|mixed $criteria)
 * @method static Datentraeger|Proxy                        findOrCreate(array $attributes)
 * @method static Datentraeger|Proxy                        first(string $sortedField = 'id')
 * @method static Datentraeger|Proxy                        last(string $sortedField = 'id')
 * @method static Datentraeger|Proxy                        random(array $attributes = [])
 * @method static Datentraeger|Proxy                        randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static Datentraeger[]|Proxy[]                    all()
 * @method static Datentraeger[]|Proxy[]                    createMany(int $number, array|callable $attributes = [])
 * @method static Datentraeger[]|Proxy[]                    createSequence(iterable|callable $sequence)
 * @method static Datentraeger[]|Proxy[]                    findBy(array $attributes)
 * @method static Datentraeger[]|Proxy[]                    randomRange(int $min, int $max, array $attributes = [])
 * @method static Datentraeger[]|Proxy[]                    randomSet(int $number, array $attributes = [])
 */
final class DatentraegerFactory extends PersistentProxyObjectFactory
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
        return Datentraeger::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'bauart' => array_rand(['intern', 'extern']),
            'formfaktor' => array_rand(['2,5', '3,5']),
            'groesse' => random_int(1, max: 4) * 10**random_int(0,3),
            'hersteller' => self::faker()->company(),
            'modell' => self::faker()->name(),
            'sn' => implode(self::faker()->randomElements(['M', 'S', 'O', 'P'], 2, true)) 
                . implode(self::faker()->randomElements(['T', 'A', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], 6, true)),
            'pn' => implode(self::faker()->randomElements(['M', 'S', 'O', 'P'], 2, true)) 
                . implode(self::faker()->randomElements(['T', 'A', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], 6, true)),
            'anschluss' => array_rand(['SATA', 'USB']),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Datentraeger $datentraeger): void {})
        ;
    }
}
