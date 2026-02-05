<?php

namespace App\Tests\Factory;

use App\Entity\CaseFile;
use App\Enum\CaseSecrecy;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<CaseFile>
 *
 * @method        CaseFile|Proxy                                create(array|callable $attributes = [])
 * @method static CaseFile|Proxy                                createOne(array $attributes = [])
 * @method static CaseFile|Proxy                                find(object|array|mixed $criteria)
 * @method static CaseFile|Proxy                                findOrCreate(array $attributes)
 * @method static CaseFile|Proxy                                first(string $sortedField = 'id')
 * @method static CaseFile|Proxy                                last(string $sortedField = 'id')
 * @method static CaseFile|Proxy                                random(array $attributes = [])
 * @method static CaseFile|Proxy                                randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static CaseFile[]|Proxy[]                            all()
 * @method static CaseFile[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static CaseFile[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static CaseFile[]|Proxy[]                            findBy(array $attributes)
 * @method static CaseFile[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static CaseFile[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 */
final class CaseFactory extends PersistentProxyObjectFactory
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
        return CaseFile::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        return [
            'beschreibung' => self::faker()->text(40),
            'DOS' => self::faker()->randomElement(
                array_map(fn($c) => $c->value, CaseSecrecy::cases())
            ),
            'zeitstempel' => self::faker()->dateTime(),
            'case_id' => self::faker()->randomLetter() . self::faker()->randomNumber(4),
            'istAktiv' => true,
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
