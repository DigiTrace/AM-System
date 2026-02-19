<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Allowed categories for assets (objects).
 *
 * @author Ben Brooksnieder
 */
enum AssetCategory: int implements TranslatableInterface
{
    case Exhibit = 0;
    case Equipment = 1;
    case Container = 2;
    case Hdd = 3;
    case Record = 4;
    case ExhibitHdd = 5;

    /**
     * Returns whether category is generally allowed to be used as a storage.
     */
    public static function storageAllowed(self $category): bool
    {
        return match ($category) {
            self::Exhibit => false,
            self::Equipment => true,
            self::Container => true,
            self::Hdd => false,
            self::Record => false,
            self::ExhibitHdd => false,
        };
    }

    /**
     * Returns list of categories which function generally as storage.
     *
     * @return self[]
     */
    public static function getStorageCategories(): array
    {
        return array_filter(self::cases(), [self::class, 'storageAllowed']);
    }

    /**
     * Returns whether this category allows storage.
     */
    public function isStorage(): bool
    {
        return self::storageAllowed($this);
    }

    /**
     * Returns whether category is a drive.
     */
    public static function isDriveCategory(self $category): bool
    {
        return match ($category) {
            self::Exhibit => false,
            self::Equipment => false,
            self::Container => false,
            self::Hdd => true,
            self::Record => false,
            self::ExhibitHdd => true,
        };
    }

    /**
     * Returns list of categories which function generally as storage.
     *
     * @return self[]
     */
    public static function getDriveCategories(): array
    {
        return array_filter(self::cases(), [self::class, 'isDriveCategory']);
    }

    /**
     * Returns whether this category allows storage.
     */
    public function isDrive(): bool
    {
        return self::isDriveCategory($this);
    }

    /**
     * Determines whether category is a target for hdd images from assets.
     */
    public static function isHddImageTargetCategory(self $category): bool
    {
        return match ($category) {
            self::Exhibit => false,
            self::Equipment => false,
            self::Container => false,
            self::Hdd => true,
            self::Record => false,
            self::ExhibitHdd => false,
        };
    }

    /**
     * Returns list of categories which are allowed targets for hdd images like HDDs.
     *
     * @return self[]
     */
    public static function getHddImageTargetCategories(): array
    {
        return array_filter(self::cases(), [self::class, 'isHddImageTargetCategory']);
    }

    /**
     * Determines whether category is a target for hdd images from assets.
     */
    public function isHddImageTarget(): bool
    {
        return self::isHddImageTargetCategory($this);
    }

    /**
     * Determines whether category is a source for hdd images that can be stored on hdds.
     */
    public static function isHddImageSourceCategory(self $category): bool
    {
        return match ($category) {
            self::Exhibit => false,
            self::Equipment => false,
            self::Container => false,
            self::Hdd => false,
            self::Record => false,
            self::ExhibitHdd => true,
        };
    }

    /**
     * Returns list of categories which are allowed sources for hdd images.
     *
     * @return self[]
     */
    public static function getHddImageSourceCategories(): array
    {
        return array_filter(self::cases(), [self::class, 'isHddImageSourceCategory']);
    }

    /**
     * Determines whether category is a source for hdd images that can be stored on hdds.
     */
    public function isHddImageSource(): bool
    {
        return self::isHddImageSourceCategory($this);
    }

    /**
     * Return dt barcode prefix.
     *
     * @return string prefix, like "DTHW"
     */
    public function getDtBarcodePrefix(): string
    {
        return match ($this) {
            self::Exhibit => 'DTAS',
            self::Equipment => 'DTHW',
            self::Container => 'DTHW',
            self::Hdd => 'DTHD',
            self::Record => 'DTAK',
            self::ExhibitHdd => 'DTAS',
        };
    }

    /**
     * Maps cases to bootstrap color label.
     */
    public function bootstrapColor(): string
    {
        return match ($this) {
            self::Exhibit => 'primary',
            self::Equipment => 'info',
            self::Container => 'success',
            self::Hdd => 'warning',
            self::Record => 'primary',
            self::ExhibitHdd => 'primary',
        };
    }

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans($this->toTranslatableString(), locale: $locale);
    }

    /**
     * Return string identifier that can be translated to category.
     *
     * @return string translation identifier
     */
    public function toTranslatableString(): string
    {
        return match ($this) {
            self::Exhibit => 'enum.asset_category.exhibit',
            self::Equipment => 'enum.asset_category.equipment',
            self::Container => 'enum.asset_category.container',
            self::Hdd => 'enum.asset_category.hdd',
            self::Record => 'enum.asset_category.record',
            self::ExhibitHdd => 'enum.asset_category.exhibit_hdd',
        };
    }
}
