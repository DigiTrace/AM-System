<?php

namespace App\Entity;

use App\Enum\AssetState as State;
use App\Repository\AssetHistoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Robert Krasowski
 * @author Ben Brooksnieder
 */
#[ORM\Entity(repositoryClass: AssetHistoryRepository::class)]
// #[ORM\Table(name: 'ams_Historie_Objekt')]
class AssetHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(name: 'historie_id')]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Asset::class, inversedBy: 'histories', fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'barcode_id', nullable: false, referencedColumnName: 'barcode_id')]
    private ?Asset $asset = null;

    #[ORM\Column(type: 'integer', enumType: State::class, name: 'status_id')]
    private State $state;

    #[ORM\Column(nullable: true, name: 'systemaktion')]
    private ?bool $systemAction = false;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'verwendung')]
    private ?string $usage = null;

    #[ORM\ManyToOne(inversedBy: 'assetHistories')]
    #[ORM\JoinColumn(nullable: false, name: 'nutzer_id')]
    private ?Nutzer $modifiedBy = null;

    #[ORM\ManyToOne(inversedBy: 'reservedAssetHistories')]
    #[ORM\JoinColumn(nullable: true, name: 'reserviert_von')]
    private ?Nutzer $reservedBy = null;

    #[ORM\ManyToOne(inversedBy: 'assetHistories')]
    #[ORM\JoinColumn(name: 'fall_id', nullable: true)]
    private ?Fall $case = null;

    #[ORM\ManyToOne(inversedBy: 'storageHistories')]
    #[ORM\JoinColumn(name: 'standort', referencedColumnName: 'barcode_id', nullable: true)]
    private ?Asset $location = null;

    #[ORM\Column(nullable: true)]
    private ?bool $storageOverride = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'zeitstempel')]
    private ?\DateTimeInterface $lastUpdatedOn = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'zeitstempelderumsetzung')]
    private ?\DateTimeInterface $lastUpdatePerformedOn = null;

    #[ORM\ManyToMany(targetEntity: Asset::class, inversedBy: 'imageHistory')]
    // #[ORM\JoinTable(name: 'ams_image_objekt')]
    #[ORM\JoinColumn(name: 'historie_id', referencedColumnName: 'historie_id')]
    #[ORM\InverseJoinColumn(name: 'barcode_id', referencedColumnName: 'barcode_id')]
    private Collection $images;

    /**
     * Returns `getAsset()->__toString()`.
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->asset->getBarcode();
    }

    /**
     * Generate AssetHistory entry based on current `$asset`'s state.
     *
     * @param Asset $asset to generate history for
     *
     * @return AssetHistory
     */
    public static function fromAsset(Asset $asset)
    {
        $entry = new self();
        $entry->setAsset($asset);
        $entry->setState($asset->getState());
        $entry->setSystemAction($asset->isSystemAction());
        $entry->setUsage($asset->getUsage());
        $entry->setModifiedBy($asset->getModifiedBy());
        $entry->setReservedBy($asset->getReservedBy());
        $entry->setCase($asset->getCase());
        $entry->setLocation($asset->getLocation());
        $entry->setStorageOverride($asset->isStorageOverride());
        $entry->setLastUpdatedOn($asset->getLastUpdatedOn());
        $entry->setLastUpdatePerformedOn($asset->getLastUpdatePerformedOn());

        foreach ($asset->getImages() as $image) {
            $entry->addImage($image);
        }

        return $entry;
    }

    public function getBarcode(){
        return $this->asset->getBarcode();
    }

    //
    // =============== AUTO GENRATED GETTER AND SETTER ===============
    //

    public function __construct()
    {
        $this->storage = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $barcode): static
    {
        $this->asset = $barcode;

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

    public function getCase(): ?Fall
    {
        return $this->case;
    }

    public function setCase(?Fall $case): static
    {
        $this->case = $case;

        return $this;
    }

    public function getLocation(): ?Asset
    {
        return $this->location;
    }

    public function setLocation(?Asset $location): static
    {
        $this->location = $location;

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
     * @return Collection<int, Asset>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Asset $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
        }

        return $this;
    }

    public function removeImage(Asset $image): static
    {
        $this->images->removeElement($image);

        return $this;
    }
}
