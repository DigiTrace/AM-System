<?php

namespace App\Tests\Factory;

use App\Entity\Fall;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<Fall>
 *
 * @method        Fall|Proxy                                create(array|callable $attributes = [])
 * @method static Fall|Proxy                                createOne(array $attributes = [])
 * @method static Fall|Proxy                                find(object|array|mixed $criteria)
 * @method static Fall|Proxy                                findOrCreate(array $attributes)
 * @method static Fall|Proxy                                first(string $sortedField = 'id')
 * @method static Fall|Proxy                                last(string $sortedField = 'id')
 * @method static Fall|Proxy                                random(array $attributes = [])
 * @method static Fall|Proxy                                randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static Fall[]|Proxy[]                            all()
 * @method static Fall[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static Fall[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static Fall[]|Proxy[]                            findBy(array $attributes)
 * @method static Fall[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static Fall[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 */
final class FallFactory extends PersistentProxyObjectFactory
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
        return Fall::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'beschreibung' => self::faker()->text(),
            'DOS' => self::faker()->text(255),
            'zeitstempel' => self::faker()->dateTime(),
            'case_id' => self::faker()->text(255),
            'istAktiv' => self::faker()->boolean(),
        ];
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(Fall $fall): void {})
        ;
    }

    public function active(): self
    {
        return $this->with(['istAktiv' => true]);
    }

    public function inactive(): self
    {
        return $this->with(['istAktiv' => false]);
    }
}
