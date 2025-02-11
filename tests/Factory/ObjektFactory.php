<?php

namespace App\Tests\Factory;

use App\Entity\Nutzer;
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
    private static bool $generateDrive = true;

    private static array $category_to_prefix = [
        Objekt::KATEGORIE_ASSERVAT => "DTAS",
        Objekt::KATEGORIE_AUSRUESTUNG => "DTHW",
        Objekt::KATEGORIE_BEHAELTER => "DTHW",
        Objekt::KATEGORIE_DATENTRAEGER => "DTHD",
        Objekt::KATEGORIE_AKTE => "DTAS",
        Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER => "DTAS",
    ];

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

    public function enableAutomaticDriveGeneration(): void{
        static::$generateDrive = true;
    }
    public function disableAutomaticDriveGeneration(): void{
        static::$generateDrive = false;
    }
    public function setAutomaticDriveGeneration(bool $enabled): void{
        static::$generateDrive = $enabled;
    }

    /**
     * Generate DT Objekt label.
     *
     * @param string|null $prefix Optional label prefix. Must be one of `'DTAS', 'DTHD', 'DTHW'`
     *
     * @return string DT barcode label
     */
    public static function generateBarcode(?string $prefix = null)
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
        // choose category
        $category = array_rand([
            Objekt::KATEGORIE_ASSERVAT,
            Objekt::KATEGORIE_AUSRUESTUNG,
            Objekt::KATEGORIE_BEHAELTER,
            Objekt::KATEGORIE_DATENTRAEGER,
            Objekt::KATEGORIE_AKTE,
            Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER,
        ]);
        
        // generate barcode based on category
        $barcode = $this->generateBarcode(static::$category_to_prefix[$category]);

        $defaults = [
            'barcode' => $barcode,
            'kategorie' => $category,
            'name' => self::faker()->text(),
            'status' => Objekt::STATUS_EINGETRAGEN,
            'zeitstempel' => self::faker()->dateTime(),
            'zeitstempelumsetzung' => self::faker()->dateTime(),
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
            if(static::$generateDrive){
                // if objekt is storage device, add entry for that with given barcode
                if (Objekt::KATEGORIE_DATENTRAEGER == $attributes['kategorie'] || Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER == $attributes['kategorie']) {
                    DatentraegerFactory::new()->create([
                        'barcode' => $attributes['barcode'],
                    ]);
                }
            }
        });
    }

    public function exhibit(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTAS'),
            'kategorie' => Objekt::KATEGORIE_ASSERVAT,
        ]);
    }

    public function equipment(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTHW'),
            'kategorie' => Objekt::KATEGORIE_AUSRUESTUNG,
        ]);
    }

    public function container(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTHW'),
            'kategorie' => Objekt::KATEGORIE_BEHAELTER, 
        ]);
    }

    public function hdd(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTHD'),
            'kategorie' => Objekt::KATEGORIE_DATENTRAEGER,
        ]);
    }

    public function record(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTAS'),
            'kategorie' => Objekt::KATEGORIE_AKTE,
        ]);
    }

    public function exhibitHdd(): self
    {
        return $this->with([
            'barcode' => $this->generateBarcode('DTAS'),
            'kategorie' => Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER,
        ]);
    }

    public function destroyed(): self
    {
        return $this->with([
            'status' => Objekt::STATUS_VERNICHTET,
        ]);
    }

    public function lost(): self
    {
        return $this->with([
            'status' => Objekt::STATUS_VERLOREN,
        ]);
    }

    public function reservedBy(Nutzer $user): self
    {
        return $this->with([
            'status' => Objekt::STATUS_RESERVIERT,
            'reserviert_von' => $user,
        ]);
    }
}
