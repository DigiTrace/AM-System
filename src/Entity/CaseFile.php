<?php

// AM-System
// Copyright (C) 2019 Robert Krasowski
// This program was created during an internship at DigiTrace GmbH
// Read LIZENZ.txt for full notice

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

namespace App\Entity;

use App\Enum\CaseSecrecy;
use App\Repository\CaseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Base entity for cases.
 *
 * @author Robert Kraswoski
 * @author Ben Brooksnieder
 */
#[ORM\Entity(repositoryClass: CaseRepository::class)]
#[ORM\Table(name: 'ams_Fall')]
class CaseFile
{
    #[ORM\Column(name: 'id', type: 'integer', nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private int $id;

    #[ORM\Column(type: 'string', length: 255, unique: true, name: 'case_id')]
    #[Assert\NotBlank]
    private string $caseId;

    #[ORM\Column(type: 'boolean', name: 'ist_aktiv')]
    private bool $active = true;

    #[ORM\Column(type: 'string', enumType: CaseSecrecy::class, length: 255, name: 'dos')]
    #[Assert\NotBlank]
    private CaseSecrecy $secrecy = CaseSecrecy::Public;

    #[ORM\Column(type: 'text', name: 'beschreibung')]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'zeitstempel_beginn')]
    private ?\DateTimeInterface $openedOn;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'zeitstempel_ende', nullable: true)]
    private ?\DateTimeInterface $closedOn = null;

    #[ORM\OneToMany(mappedBy: 'case', targetEntity: Asset::class)]
    private Collection $assets;

    #[ORM\OneToMany(mappedBy: 'case', targetEntity: AssetHistory::class)]
    private Collection $assetHistories;

    //
    // =============== AUTO GENRATED GETTER AND SETTER ===============
    //

    public function __construct()
    {
        // default time is now
        $this->openedOn = new \DateTime('now');
        $this->assets = new ArrayCollection();
        $this->assetHistories = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCaseId(): string
    {
        return $this->caseId;
    }

    public function setCaseId(string $caseId): static
    {
        $this->caseId = $caseId;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    public function getSecrecy(): CaseSecrecy
    {
        return $this->secrecy;
    }

    public function setSecrecy(CaseSecrecy $secrecy): static
    {
        $this->secrecy = $secrecy;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getOpenedOn(): ?\DateTimeInterface
    {
        return $this->openedOn;
    }

    public function setOpenedOn(\DateTimeInterface $openedOn): static
    {
        $this->openedOn = $openedOn;

        return $this;
    }

    public function getClosedOn(): ?\DateTimeInterface
    {
        return $this->closedOn;
    }

    public function setClosedOn(?\DateTimeInterface $closedOn): static
    {
        $this->closedOn = $closedOn;

        return $this;
    }

    /**
     * @return Collection<int, Asset>
     */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): static
    {
        if (!$this->assets->contains($asset)) {
            $this->assets->add($asset);
            $asset->setCase($this);
        }

        return $this;
    }

    public function removeAsset(Asset $asset): static
    {
        if ($this->assets->removeElement($asset)) {
            // set the owning side to null (unless already changed)
            if ($asset->getCase() === $this) {
                $asset->setCase(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AssetHistory>
     */
    public function getAssetHistories(): Collection
    {
        return $this->assetHistories;
    }

    public function addAssetHistory(AssetHistory $assetHistory): static
    {
        if (!$this->assetHistories->contains($assetHistory)) {
            $this->assetHistories->add($assetHistory);
            $assetHistory->setCase($this);
        }

        return $this;
    }

    public function removeAssetHistory(AssetHistory $assetHistory): static
    {
        if ($this->assetHistories->removeElement($assetHistory)) {
            // set the owning side to null (unless already changed)
            if ($assetHistory->getCase() === $this) {
                $assetHistory->setCase(null);
            }
        }

        return $this;
    }
}
