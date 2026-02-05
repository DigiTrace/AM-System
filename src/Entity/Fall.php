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
use App\Repository\FallRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity(repositoryClass: FallRepository::class)]
#[ORM\Table(name: "ams_Fall")]
class Fall
{    
    public function __construct() {
        $time = new \DateTime('NOW');
        $this->zeitstempel_beginn = $time;
        $this->assetHistories = new ArrayCollection();
    }
    

    #[ORM\Column(type: "boolean")]
    protected $istAktiv = true;
    
    
    
    #[ORM\Column(name: "id",type: "integer", nullable: false)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    protected $id;
    
    public function getId(){
        return $this->id;
    }
    
    public function setId($newid){
        $this->id =$newid;
    }
    
    
    #[ORM\Column(type: "string",length: 255)]
    #[Assert\NotBlank]
    protected $case_id;
    
    
    public function getCaseId(){
        return $this->case_id;
    }
    
    public function setCaseId($caseid){
        $this->case_id = $caseid;
    }
    
    
     
    #[ORM\Column(type: "string",length: 255)]
    #[Assert\NotBlank]   
    protected $DOS = CaseSecrecy::Public->value;
    
    public function getDOS(){
        return $this->DOS;
    }
    
    public function setDOS($dos){
        $this->DOS = $dos;
    }
    
    
    
    
    #[ORM\Column(type: "text")]
    #[Assert\NotBlank] 
    protected $beschreibung;
    
    
    
    #[ORM\Column(type: "datetime")]
    protected $zeitstempel_beginn;

    
    #[ORM\Column(type:"datetime",nullable:true)]
    protected $zeitstempel_ende;

  

    
    #[ORM\OneToMany(targetEntity: "Asset", mappedBy: "fall_id")]
    protected $assets;

    #[ORM\OneToMany(mappedBy: 'case', targetEntity: AssetHistory::class)]
    private Collection $assetHistories;
    
    
    
    /**
     * Add asset
     *
     * @param \App\Entity\Asset $asset
     *
     * @return Fall
     */
    public function addAsset(\App\Entity\Asset $asset)
    {
        $this->assets[] = $asset;

        return $this;
    }

    /**
     * Remove asset
     *
     * @param \App\Entity\Asset $asset
     */
    public function removeAsset(\App\Entity\Asset $asset)
    {
        $this->assets->removeElement($asset);
    }
    
    
    
    public function getAssets(){
        return $this->assets;
    }

    
    
    public function getBeschreibung() {
        return $this->beschreibung;
    }
        
    public function getZeitstempel(){
        return $this->zeitstempel_beginn;
    }

    public function istAktiv(){
        return $this->istAktiv;
    }
    public function setistAktiv($switch){
        $this->istAktiv = $switch;
    }
    
   /*public function getNewId(){
        return $this->newid;
    }
    
    public function setNewId($newid){
        $this->newid = $newid;
    }*/
    

    /**
     * Get fallId
     *
     * @return integer
     */
   /* public function getFallId()
    {
        return $this->fall_id;
    }*/

    /**
     * Set beschreibung
     *
     * @param string $beschreibung
     *
     * @return Fall
     */
    public function setBeschreibung($beschreibung)
    {
        $this->beschreibung = $beschreibung;

        return $this;
    }

    /**
     * Set zeitstempel
     *
     * @param \DateTime $zeitstempel
     *
     * @return Fall
     */
    public function setZeitstempel($zeitstempel)
    {
        $this->zeitstempel_beginn = $zeitstempel;

        return $this;
    }
    
    
    /**
     * Get DOS_LIST
     * @return array<CaseSecrecy>
     */
    static function getDOSList()
    {
        return CaseSecrecy::cases();
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
