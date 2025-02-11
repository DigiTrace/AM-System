<?php

namespace App\Tests\Factory;

use App\Entity\HistorieObjekt;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<HistorieObjekt>
 *
 * @method        HistorieObjekt|Proxy                     create(array|callable $attributes = [])
 * @method static HistorieObjekt|Proxy                     createOne(array $attributes = [])
 * @method static HistorieObjekt|Proxy                     find(object|array|mixed $criteria)
 * @method static HistorieObjekt|Proxy                     findOrCreate(array $attributes)
 * @method static HistorieObjekt|Proxy                     first(string $sortedField = 'id')
 * @method static HistorieObjekt|Proxy                     last(string $sortedField = 'id')
 * @method static HistorieObjekt|Proxy                     random(array $attributes = [])
 * @method static HistorieObjekt|Proxy                     randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static HistorieObjekt[]|Proxy[]                 all()
 * @method static HistorieObjekt[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static HistorieObjekt[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static HistorieObjekt[]|Proxy[]                 findBy(array $attributes)
 * @method static HistorieObjekt[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static HistorieObjekt[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 */
final class HistorieObjektFactory extends PersistentProxyObjectFactory
{
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     */
    public function __construct()
    {
    }

    public static function class(): string
    {
        return HistorieObjekt::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        $fac = ObjektFactory::new();
        $defaults = array_intersect_key($fac->defaults(), array_flip([
            'barcode', 'nutzer', 'status', 'zeitstempel', 'zeitstempelderumsetzung'
        ]));
        $defaults['systemaktion'] = self::faker()->boolean();
     
        return $defaults;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
            // ->afterInstantiate(function(HistorieObjekt $HistorieObjekt): void {})
        ;
    }
}
