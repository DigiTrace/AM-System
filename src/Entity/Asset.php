<?php

namespace App\Entity;

use App\Enum\AssetCategory as Category;
use App\Enum\AssetState as State;
use App\Repository\AssetRepository;
use App\Validator as AppConstraints;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Base entity class for all assets (objects) managed by the system.
 *
 * @author Robert Krasowski
 * @author Ben Brooksnieder
 */
#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\Table(name: 'ams_Objekt')]
class Asset
{
    #[ORM\Column(length: 9, name: 'barcode_id', updatable: false)]
    #[ORM\Id]
    private ?string $barcode = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', enumType: Category::class, name: 'kategorie_id', nullable: false)]
    private Category $category;

    #[ORM\Column(type: 'integer', enumType: State::class, name: 'status_id', nullable: false)]
    // #[AppConstraints\AssetState(groups: ['AssetState'])]
    private State $state;

    #[ORM\Column(nullable: true, name: 'systemaktion')]
    private ?bool $systemAction = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'verwendung')]
    private ?string $usage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'notiz')]
    private ?string $note = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, name: 'nutzer_id')]
    private ?Nutzer $modifiedBy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, name: 'reserviert_von')]
    private ?Nutzer $reservedBy = null;

    #[ORM\ManyToOne(inversedBy: 'assets')]
    #[ORM\JoinColumn(name: 'fall_id', nullable: true)]
    // #[AppConstraints\AssetCase()]
    private ?CaseFile $case = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'storage')]
    #[ORM\JoinColumn(name: 'standort', referencedColumnName: 'barcode_id', nullable: true)]
    // #[AppConstraints\AssetLocation()]
    private ?self $location = null;

    #[ORM\OneToMany(mappedBy: 'location', targetEntity: self::class)]
    private Collection $storage;

    #[ORM\Column(nullable: true)]
    private ?bool $storageOverride = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'zeitstempel')]
    private ?\DateTimeInterface $lastUpdatedOn = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'zeitstempelderumsetzung')]
    private ?\DateTimeInterface $lastUpdatePerformedOn = null;

    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'hdds')]
    private Collection $images;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'images')]
    #[ORM\JoinTable(name: 'ams_ZuordnungImageToHDD')]
    #[ORM\JoinColumn(name: 'image', referencedColumnName: 'barcode_id')]
    #[ORM\InverseJoinColumn(name: 'hdd', referencedColumnName: 'barcode_id')]
    private Collection $hdds;

    #[ORM\OneToOne(mappedBy: 'asset', cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY')]
    private ?AssetBlob $assetBlob = null;

    #[ORM\OneToMany(mappedBy: 'asset', targetEntity: AssetHistory::class)]
    private Collection $histories;

    #[ORM\OneToMany(mappedBy: 'location', targetEntity: AssetHistory::class)]
    private Collection $storageHistories;

    #[ORM\ManyToMany(targetEntity: AssetHistory::class, mappedBy: 'images')]
    private Collection $imageHistory;

    #[ORM\OneToOne(mappedBy: 'barcode', cascade: ['persist', 'remove'])]
    private ?Drive $drive = null;

    /**
     * Determines whether asset is allowed to be used as storage.
     * Checks `$storageOverride` attribute with `AssetCategory::storageAllowed` as fallback.
     */
    public function isStorage(): bool
    {
        if (null !== $this->storageOverride) {
            return (bool) $this->storageOverride;
        }

        return $this->category->isStorage();
    }

    /**
     * Determines whether asset is a drive.
     */
    public function isDrive(): bool
    {
        return $this->category->isDrive();
    }

    /**
     * Determines whether asset allows general editing of attributes.
     */
    public function isEditable(): bool
    {
        return $this->state->isEditable();
    }

    /**
     * Determines whether asset is allowed to be removed from case assing.
     */
    public function isRemovableFromCase(): bool
    {
                
        return Category::Record !== $this->category;
    }

    /**
     * Determines whether asset is a target for hdd images from other sources.
     *
     * @return bool Currently `Category::Hdd === $this->category`
     */
    public function isHddImageTarget(): bool
    {
        return $this->category->isHddImageTarget();
    }

    /**
     * Determines whether asset is a source for hdd images that can be stored on hdds.
     *
     * @return bool Currently `Category::ExhibitHdd === $this->category`
     */
    public function isHddImageSource(): bool
    {
        return $this->category->isHddImageSource();
    }

    /**
     * Set picture.
     *
     * @param mixed $streamId
     */
    public function setPicture(?string $streamId): static
    {
        if (null === $this->assetBlob) {
            $this->assetBlob = new AssetBlob($this);
        }

        $this->assetBlob->setPicture($streamId);

        return $this;
    }

    /**
     * Get picture as base64 encoded blob.
     *
     * @return string|null base64 encoded blob
     */
    public function getPicture(): ?string
    {
        return $this->assetBlob?->getPicture();
    }

    /**
     * Set picture path.
     */
    public function setPicturePath(?string $path): static
    {
        if (null === $this->assetBlob) {
            $this->assetBlob = new AssetBlob($this);
        }

        $this->assetBlob->setPath($path);

        return $this;
    }

    /**
     * Get picture path.
     *
     * @return string|null
     */
    public function getPicturePath(): ?string
    {
        return $this->assetBlob?->getPath();
    }

    /**
     * Returns `getBarcode()`.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getBarcode();
    }

    //
    // =============== AUTO GENRATED GETTER AND SETTER ===============
    //

    public function __construct()
    {
        $this->storage = new ArrayCollection();
        $this->images = new ArrayCollection();
        $this->hdds = new ArrayCollection();
        $this->histories = new ArrayCollection();
        $this->storageHistories = new ArrayCollection();
        $this->imageHistory = new ArrayCollection();
    }

    public function getBarcode(): ?string
    {
        return $this->barcode;
    }

    public function setBarcode(string $barcode): static
    {
        $this->barcode = $barcode;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getState(): State
    {
        return $this->state;
    }

    public function setState(State $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function isSystemAction(): ?bool
    {
        return $this->systemAction;
    }

    public function setSystemAction(?bool $systemAction): static
    {
        $this->systemAction = $systemAction;

        return $this;
    }

    public function getUsage(): ?string
    {
        return $this->usage;
    }

    public function setUsage(?string $usage): static
    {
        $this->usage = $usage;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getModifiedBy(): ?Nutzer
    {
        return $this->modifiedBy;
    }

    public function setModifiedBy(?Nutzer $modifiedBy): static
    {
        $this->modifiedBy = $modifiedBy;

        return $this;
    }

    public function getReservedBy(): ?Nutzer
    {
        return $this->reservedBy;
    }

    public function setReservedBy(?Nutzer $reservedBy): static
    {
        $this->reservedBy = $reservedBy;

        return $this;
    }

    public function getCase(): ?CaseFile
    {
        return $this->case;
    }

    public function setCase(?CaseFile $case): static
    {
        $this->case = $case;

        return $this;
    }

    public function getLocation(): ?self
    {
        return $this->location;
    }

    public function setLocation(?self $location): static
    {
        $this->location = $location;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getStorage(): Collection
    {
        return $this->storage;
    }

    public function addStorage(self $storage): static
    {
        if (!$this->storage->contains($storage)) {
            $this->storage->add($storage);
            $storage->setLocation($this);
        }

        return $this;
    }

    public function removeStorage(self $storage): static
    {
        if ($this->storage->removeElement($storage)) {
            // set the owning side to null (unless already changed)
            if ($storage->getLocation() === $this) {
                $storage->setLocation(null);
            }
        }

        return $this;
    }

    public function isStorageOverride(): ?bool
    {
        return $this->storageOverride;
    }

    public function setStorageOverride(?bool $storageOverride): static
    {
        $this->storageOverride = $storageOverride;

        return $this;
    }

    public function getLastUpdatedOn(): ?\DateTimeInterface
    {
        return $this->lastUpdatedOn;
    }

    public function setLastUpdatedOn(\DateTimeInterface $lastUpdatedOn): static
    {
        $this->lastUpdatedOn = $lastUpdatedOn;

        return $this;
    }

    public function getLastUpdatePerformedOn(): ?\DateTimeInterface
    {
        return $this->lastUpdatePerformedOn;
    }

    public function setLastUpdatePerformedOn(\DateTimeInterface $lastUpdatePerformedOn): static
    {
        $this->lastUpdatePerformedOn = $lastUpdatePerformedOn;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function flushImages(): static
    {
        if ($this->isHddImageTarget()) {
            $this->images->clear();
        }

        return $this;
    }

    public function addImage(self $image): static
    {
        if ($this->isHddImageTarget()) {
            if (!$this->images->contains($image)) {
                $this->images->add($image);
            }
        }

        return $this;
    }

    public function removeImage(self $image): static
    {
        if ($this->isHddImageTarget()) {
            $this->images->removeElement($image);
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getHdds(): Collection
    {
        return $this->hdds;
    }

    public function flushHdd(): static
    {
        if ($this->isHddImageSource()) {
            $this->hdds->clear();
        }

        return $this;
    }

    public function addHdd(self $hdd): static
    {
        if ($this->isHddImageSource()) {
            if (!$this->hdds->contains($hdd)) {
                $this->hdds->add($hdd);
                $hdd->addImage($this);
            }
        }

        return $this;
    }

    public function removeHdd(self $hdd): static
    {
        if ($this->isHddImageSource()) {
            if ($this->hdds->removeElement($hdd)) {
                $hdd->removeImage($this);
            }
        }

        return $this;
    }

    public function getBlob(): ?AssetBlob
    {
        return $this->assetBlob;
    }

    public function setBlob(?AssetBlob $assetBlob): static
    {
        $this->assetBlob = $assetBlob;

        return $this;
    }

    /**
     * @return Collection<int, AssetHistory>
     */
    public function getHistories(): Collection
    {
        return $this->histories;
    }

    public function addHistory(AssetHistory $history): static
    {
        if (!$this->histories->contains($history)) {
            $this->histories->add($history);
            $history->setAsset($this);
        }

        return $this;
    }

    public function removeHistory(AssetHistory $history): static
    {
        if ($this->histories->removeElement($history)) {
            // set the owning side to null (unless already changed)
            if ($history->getAsset() === $this) {
                $history->setAsset(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AssetHistory>
     */
    public function getStorageHistories(): Collection
    {
        return $this->storageHistories;
    }

    public function addStorageHistory(AssetHistory $storageHistory): static
    {
        if (!$this->storageHistories->contains($storageHistory)) {
            $this->storageHistories->add($storageHistory);
            $storageHistory->setLocation($this);
        }

        return $this;
    }

    public function removeStorageHistory(AssetHistory $storageHistory): static
    {
        if ($this->storageHistories->removeElement($storageHistory)) {
            // set the owning side to null (unless already changed)
            if ($storageHistory->getLocation() === $this) {
                $storageHistory->setLocation(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AssetHistory>
     */
    public function getImageHistory(): Collection
    {
        return $this->imageHistory;
    }

    public function addImageHistory(AssetHistory $imageHistory): static
    {
        if (!$this->imageHistory->contains($imageHistory)) {
            $this->imageHistory->add($imageHistory);
            $imageHistory->addImage($this);
        }

        return $this;
    }

    public function removeImageHistory(AssetHistory $imageHistory): static
    {
        if ($this->imageHistory->removeElement($imageHistory)) {
            $imageHistory->removeImage($this);
        }

        return $this;
    }

    public function getDrive(): ?Drive
    {
        return $this->drive;
    }

    public function setDrive(?Drive $drive): static
    {
        // set the owning side of the relation if necessary
        if ($drive !== null && $drive->getBarcode() !== $this) {
            $drive->setBarcode($this);
        }

        $this->drive = $drive;

        return $this;
    }
}
