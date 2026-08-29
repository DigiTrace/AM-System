<?php

namespace App\Tests\Factory;

use App\Entity\Drive;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<Drive>
 *
 * @method        Drive|Proxy                        create(array|callable $attributes = [])
 * @method static Drive|Proxy                        createOne(array $attributes = [])
 * @method static Drive|Proxy                        find(object|array|mixed $criteria)
 * @method static Drive|Proxy                        findOrCreate(array $attributes)
 * @method static Drive|Proxy                        first(string $sortedField = 'id')
 * @method static Drive|Proxy                        last(string $sortedField = 'id')
 * @method static Drive|Proxy                        random(array $attributes = [])
 * @method static Drive|Proxy                        randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static Drive[]|Proxy[]                    all()
 * @method static Drive[]|Proxy[]                    createMany(int $number, array|callable $attributes = [])
 * @method static Drive[]|Proxy[]                    createSequence(iterable|callable $sequence)
 * @method static Drive[]|Proxy[]                    findBy(array $attributes)
 * @method static Drive[]|Proxy[]                    randomRange(int $min, int $max, array $attributes = [])
 * @method static Drive[]|Proxy[]                    randomSet(int $number, array $attributes = [])
 */
final class DriveFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return Drive::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        return [
            'formFactor' => self::faker()->randomElement(['2,5', '3,5']),
            'type' => self::faker()->randomElement(['intern', 'extern']),
            'size' => random_int(1, max: 4) * 10**random_int(0,3),
            'manufacturer' => self::faker()->company(),
            'model' => self::faker()->name(),
            'serialNumber' => implode(self::faker()->randomElements(['M', 'S', 'O', 'P'], 2, true)) 
                . implode(self::faker()->randomElements(['T', 'A', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], 6, true)),
            'productNumber' => implode(self::faker()->randomElements(['M', 'S', 'O', 'P'], 2, true)) 
                . implode(self::faker()->randomElements(['T', 'A', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'], 6, true)),
            'connector' => self::faker()->randomElement(['SATA', 'USB']),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Drive $drive): void {})
        ;
    }
}
