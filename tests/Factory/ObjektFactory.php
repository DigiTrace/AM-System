<?php

namespace App\Tests\Factory;

use App\Entity\Objekt;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<Objekt>
 *
 * @method        Objekt|Proxy                              create(array|callable $attributes = [])
 * @method static Objekt|Proxy                              createOne(array $attributes = [])
 * @method static Objekt|Proxy                              find(object|array|mixed $criteria)
 * @method static Objekt|Proxy                              findOrCreate(array $attributes)
 * @method static Objekt|Proxy                              first(string $sortedField = 'id')
 * @method static Objekt|Proxy                              last(string $sortedField = 'id')
 * @method static Objekt|Proxy                              random(array $attributes = [])
 * @method static Objekt|Proxy                              randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static Objekt[]|Proxy[]                          all()
 * @method static Objekt[]|Proxy[]                          createMany(int $number, array|callable $attributes = [])
 * @method static Objekt[]|Proxy[]                          createSequence(iterable|callable $sequence)
 * @method static Objekt[]|Proxy[]                          findBy(array $attributes)
 * @method static Objekt[]|Proxy[]                          randomRange(int $min, int $max, array $attributes = [])
 * @method static Objekt[]|Proxy[]                          randomSet(int $number, array $attributes = [])
 */
final class ObjektFactory extends PersistentProxyObjectFactory
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
        return Objekt::class;
    }

    /**
     * Generate DT Objekt label.
     *
     * @param string|null $prefix Optional label prefix. Must be one of `'DTAS', 'DTHD', 'DTHW'`
     *
     * @return string DT barcode label
     */
    public function generateBarcode(?string $prefix = null)
    {
        $types = ['DTAS', 'DTHD', 'DTHW'];

        if (null === $prefix) {
            $prefix = array_rand($types);
        }

        $code = $prefix.self::faker()->randomNumber(5);

        return $code;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
    protected function defaults(): array|callable
    {
        $defaults = [
            'barcode' => $this->generateBarcode(),
            'Kategorie' => array_rand([
                Objekt::KATEGORIE_ASSERVAT,
                Objekt::KATEGORIE_AUSRUESTUNG,
                Objekt::KATEGORIE_BEHAELTER,
                Objekt::KATEGORIE_AKTE,
            ]),
            'name' => self::faker()->text(),
            'Status' => self::faker()->randomNumber(),
            'zeitstempel' => self::faker()->dateTime(),
            'Zeitstempelumsetzung' => self::faker()->dateTime(),
            'nutzer' => LazyValue::memoize(fn () => NutzerFactory::createOne()),
        ];

        return $defaults;
    }

    /**
     * Default values for exhibit data.
     */
    protected function defaults_exhibit(): array|callable
    {
        $defaults = [
            'barcode' => $this->generateBarcode('DTAS'),
            'Kategorie' => Objekt::KATEGORIE_ASSERVAT,
            'name' => self::faker()->text(),
            'Status' => self::faker()->randomNumber(),
            'zeitstempel' => self::faker()->dateTime(),
            'Zeitstempelumsetzung' => self::faker()->dateTime(),
            'nutzer' => LazyValue::memoize(fn () => NutzerFactory::createOne()),
        ];

        return $defaults;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
        ->afterPersist(function (Objekt $objekt, array $attributes) {
            // if objekt is storage device, add entry for that with given barcode
            if (Objekt::KATEGORIE_DATENTRAEGER == $attributes['Kategorie'] || Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER == $attributes['Kategorie']) {
                DatentraegerFactory::new()->create([
                    'barcode' => $attributes['barcode'],
                ]);
            }
        });
    }

    public function exhibit(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTAS'),
            'Kategorie' => Objekt::KATEGORIE_ASSERVAT,
        ]);
    }

    public function equipment(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTHW'),
            'Kategorie' => Objekt::KATEGORIE_AUSRUESTUNG,
        ]);
    }

    public function container(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTHW'),
            'Kategorie' => Objekt::KATEGORIE_BEHAELTER, ]);
    }

    public function hdd(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTHD'),
            'Kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
        ]);
    }

    public function record(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTAS'),
            'Kategorie' => Objekt::KATEGORIE_AKTE,
        ]);
    }

    public function exhibitHdd(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTAS'),
            'Kategorie' => Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER,
        ]);
    }
}
