<?php

namespace App\Entity;

use App\Repository\DriveRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * @author Robert Krasowski
 * @author Ben Brooksnieder
 */
#[ORM\Entity(repositoryClass: DriveRepository::class)]
#[ORM\Table(name: 'ams_Datentraeger')]
class Drive
{
    #[ORM\ManyToOne(inversedBy: 'drive', cascade: ['persist', 'remove'])]
    #[ORM\Id]
    #[ORM\JoinColumn(name: 'barcode_id', referencedColumnName: 'barcode_id')]
    private Asset $asset;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'formfaktor')]
    private ?string $formFactor = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'bauart')]
    private ?string $type = null;

    #[ORM\Column(nullable: true, name: 'groesse')]
    private ?int $size = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'hersteller')]
    private ?string $manufacturer = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'modell')]
    private ?string $model = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'sn')]
    private ?string $serialNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'pn')]
    private ?string $productNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, name: 'anschluss')]
    private ?string $connector = null;


    /**
     * Required for data fixtures
     */
    public function __construct(array $data = []) {
        if (!$data) {
            return;
        }

        $this->setFormFactor($data['formFactor']);
        $this->setType($data['type']);
        $this->setSize($data['size']);
        $this->setManufacturer($data['manufacturer']);
        $this->setModel($data['model']);
        $this->setSerialNumber($data['serial_number']);
        $this->setProductNumber($data['product_number']);
        $this->setConnector($data['connector']);
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function setAsset(Asset $asset): static
    {
        $this->asset = $asset;

        return $this;
    }

    public function getFormFactor(): ?string
    {
        return $this->formFactor;
    }

    public function setFormFactor(?string $formFactor): static
    {
        $this->formFactor = $formFactor;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(?int $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getManufacturer(): ?string
    {
        return $this->manufacturer;
    }

    public function setManufacturer(?string $manufacturer): static
    {
        $this->manufacturer = $manufacturer;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function getSerialNumber(): ?string
    {
        return $this->serialNumber;
    }

    public function setSerialNumber(?string $serialNumber): static
    {
        $this->serialNumber = $serialNumber;

        return $this;
    }

    public function getProductNumber(): ?string
    {
        return $this->productNumber;
    }

    public function setProductNumber(?string $productNumber): static
    {
        $this->productNumber = $productNumber;

        return $this;
    }

    public function getConnector(): ?string
    {
        return $this->connector;
    }

    public function setConnector(?string $connector): static
    {
        $this->connector = $connector;

        return $this;
    }
}
