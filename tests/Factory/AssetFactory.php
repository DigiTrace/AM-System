<?php

namespace App\Tests\Factory;

use App\Entity\Asset;
use App\Entity\CaseFile;
use App\Entity\Nutzer;
use App\Enum\AssetCategory;
use App\Enum\AssetState;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\Proxy;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;

/**
 * @extends PersistentProxyObjectFactory<Asset>
 *
 * @method        Asset|Proxy                               create(array|callable $attributes = [])
 * @method static Asset|Proxy                               createOne(array $attributes = [])
 * @method static Asset|Proxy                               find(object|array|mixed $criteria)
 * @method static Asset|Proxy                               findOrCreate(array $attributes)
 * @method static Asset|Proxy                               first(string $sortedField = 'id')
 * @method static Asset|Proxy                               last(string $sortedField = 'id')
 * @method static Asset|Proxy                               random(array $attributes = [])
 * @method static Asset|Proxy                               randomOrCreate(array $attributes = [])
 * @method static EntityRepository|ProxyRepositoryDecorator repository()
 * @method static Asset[]|Proxy[]                           all()
 * @method static Asset[]|Proxy[]                           createMany(int $number, array|callable $attributes = [])
 * @method static Asset[]|Proxy[]                           createSequence(iterable|callable $sequence)
 * @method static Asset[]|Proxy[]                           findBy(array $attributes)
 * @method static Asset[]|Proxy[]                           randomRange(int $min, int $max, array $attributes = [])
 * @method static Asset[]|Proxy[]                           randomSet(int $number, array $attributes = [])
 */
final class AssetFactory extends PersistentProxyObjectFactory
{
    private static bool $generateDrive = true;

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct()
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return Asset::class;
    }

    public function enableAutomaticDriveGeneration(): void
    {
        static::$generateDrive = true;
    }

    public function disableAutomaticDriveGeneration(): void
    {
        static::$generateDrive = false;
    }

    public function setAutomaticDriveGeneration(bool $enabled): void
    {
        static::$generateDrive = $enabled;
    }

    /**
     * Generate DT asset label.
     *
     * @return string DT barcode label
     */
    public function generateBarcode(AssetCategory $category)
    {
        return $category->getDtBarcodePrefix().str_pad(self::faker()->randomNumber(5), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        $defaults = [
            'name' => self::faker()->text(10),
            'note' => self::faker()->text(20),
            'category' => self::faker()->randomElement(AssetCategory::cases()),
            'state' => AssetState::Added, // for simplicity, only support newly added assets
            'modifiedBy' => LazyValue::memoize([NutzerFactory::class, 'createOne']),
            'lastUpdatedOn' => self::faker()->dateTime(),
            'lastUpdatePerformedOn' => self::faker()->dateTime(),
            'storageOverride' => null,
        ];

        return $defaults;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
    protected function initialize(): static
    {
        return $this
        ->beforeInstantiate(function (array $parameters) {
            if (!isset($parameters['barcode'])) {
                // generate barcode if not set yet
                $parameters['barcode'] = $this->generateBarcode($parameters['category']);
            }

            return $parameters;
        })
        ->afterPersist(function (Asset $asset, array $attributes) {
            if (static::$generateDrive) {
                // if asset is storage device, add entry for that with given barcode
                if ($asset->isDrive()) {
                    DriveFactory::new()->create([
                        'barcode' => $asset,
                    ]);
                }
            }
        });
    }

    public function exhibit(): self
    {
        return $this->with([
            'category' => AssetCategory::Exhibit,
        ]);
    }

    public function equipment(): self
    {
        return $this->with([
            'category' => AssetCategory::Equipment,
        ]);
    }

    public function container(): self
    {
        return $this->with([
            'category' => AssetCategory::Container,
        ]);
    }

    public function hdd(): self
    {
        return $this->with([
            'category' => AssetCategory::Hdd,
        ]);
    }

    public function record(): self
    {
        return $this->with([
            'category' => AssetCategory::Record,
        ]);
    }

    public function exhibitHdd(): self
    {
        return $this->with([
            'category' => AssetCategory::ExhibitHdd,
        ]);
    }

    public function destroyed(): self
    {
        return $this->with([
            'state' => AssetState::Destroyed,
        ]);
    }

    public function lost(): self
    {
        return $this->with([
            'state' => AssetState::Lost,
        ]);
    }

    public function reservedBy(Nutzer $user): self
    {
        return $this->with([
            'state' => AssetState::Reserved,
            'reservedBy' => $user,
        ]);
    }

    public function storedIn(Asset $container): self 
    {
        return $this->with([
            'state' => AssetState::StoredInContainer,
            'location' => $container,
        ]);
    }

    public function assignedToCase(CaseFile $case): self 
    {
        return $this->with([
            'state' => AssetState::AssignedCase,
            'case' => $case,
        ]);
    }
}
