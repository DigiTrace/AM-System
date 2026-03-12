<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Robert Krasowski
 * @author Ben Brooksnieder
 */
#[ORM\Entity]
// #[ORM\Table(name: "ams_ObjektBlob")]
class AssetBlob
{
    #[ORM\OneToOne(inversedBy: 'assetBlob', cascade: ['persist', 'remove'])]
    #[ORM\Id]
    #[ORM\JoinColumn(name: 'barcode_id', referencedColumnName: 'barcode_id', nullable: false)]
    private Asset $asset;

    #[ORM\Column(type: 'text', nullable: true, name: 'bild')]
    #[Assert\File(mimeTypes: ['image/jpeg'])]
    protected ?string $picture;

    #[ORM\Column(type: 'string', nullable: true, name: 'bild_pfad')]
    #[Assert\File(mimeTypes: ['image/jpeg'])]
    private ?string $path;

    public function __construct(Asset $asset)
    {
        $this->asset = $asset;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(?Asset $asset): static
    {
        // unset the owning side of the relation if necessary
        if (null === $asset && null !== $this->asset) {
            $this->asset->setBlob(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $asset && $asset->getBlob() !== $this) {
            $asset->setBlob($this);
        }

        $this->asset = $asset;

        return $this;
    }

    /**
     * Set picture
     * @param mixed $streamId
     * @return AssetBlob
     */
    public function setPicture(?string $streamId): static
    {
        if (empty($streamId)) {
            $this->picture = null;
        } else {
            $strm = fopen($streamId, 'rb');
            $this->picture = base64_encode(stream_get_contents($strm));
        }

        return $this;
    }

    /**
     * Get Pic as Resource.
     *
     * @return string
     */
    public function getPicture()
    {
        return $this->picture;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path): static
    {
        $this->path = $path;

        return $this;
    }
}
