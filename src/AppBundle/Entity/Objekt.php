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
   

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use AppBundle\Controller\helper;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\Entity
 * @ORM\Table(name="ams_Objekt")
 */
class Objekt
{
    const KATEGORIE_ASSERVAT = 0;
    const KATEGORIE_AUSRUESTUNG = 1;
    const KATEGORIE_BEHAELTER = 2;
    const KATEGORIE_DATENTRAEGER = 3;
    const KATEGORIE_AKTE = 4;
    const KATEGORIE_ASSERVAT_DATENTRAEGER = 5;
    
    public function __construct() {
        $this->Zeitstempel = new \DateTime();
        $this->Zeitstempelderumsetzung = new \DateTime();
        
        $this->HDDs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->Images = new \Doctrine\Common\Collections\ArrayCollection();
        
    }

    /**
     * 
     * @ORM\Column(type="string",length=9)
     * @ORM\Id
     * @ORM\OneToMany(targetEntity="Objekt",mappedBy="Standort")
     */
    protected $Barcode_id;

    /**
     * @ORM\Column(type="text")
     */
    protected $Name;
    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $Verwendung;
    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $Notiz;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $Kategorie_id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Nutzer")
     * @ORM\JoinColumn(name="nutzer_id",referencedColumnName="id",nullable=false)
     */
    protected $Nutzer_id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Nutzer")
     * @ORM\JoinColumn(name="reserviert_von",referencedColumnName="id",nullable=true)
     */
    protected $Reserviert_von;
    
     /**
     * @ORM\ManyToOne(targetEntity="Fall", inversedBy="objekte")
     * @ORM\JoinColumn(name="fall_id",referencedColumnName="id")
     */
    protected $Fall_id;
    
    /**
     * @ORM\Column(type="integer")
     */
    protected $Status_id;
    
    /**
     * 
     * @ORM\Column(type="boolean",nullable=true)
     */
    protected $Systemaktion = false;
    
    /**
     * @ORM\ManyToOne(targetEntity="Objekt")
     * @ORM\JoinColumn(name="standort", referencedColumnName="barcode_id")
     */
    protected $Standort;
    
    /**
     * @ORM\Column(type="datetime")
     */
    protected $Zeitstempel;
    
    /**
     * @ORM\Column(type="datetime")
     */
    protected $Zeitstempelderumsetzung;
    
   
    /**
     * @ORM\Column(type="text",nullable=true)
     * @Assert\File(mimeTypes={ "image/jpeg" })
     */
    protected $Bild;
    
    /**
     * @ORM\Column(type="string",nullable=true)
     * @Assert\File(mimeTypes={ "image/jpeg" })
     */
    private $BildPfad;
    
    
    
    /**
     * Viele Asservate sind in vielen HDDs gespeichert
     * @ORM\ManyToMany(targetEntity="Objekt", mappedBy="HDDs")
     */
    private $Images;
    
    
    
    
    /**
     * Viele HDDs haben viele Asservate
     * @ORM\ManyToMany(targetEntity="Objekt", inversedBy="Images")
     * @ORM\JoinTable(name="ams_ZuordnungImageToHDD",
     *      joinColumns={@ORM\JoinColumn(name="image", referencedColumnName="barcode_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="hdd", referencedColumnName="barcode_id")}
     *      )
     */
    private $HDDs;
    
    
  
    public function __toString()
    {
      return $this->getBarcode(); // if you have a name property you can do $this->getName();
    }
    
    

    /**
     * Set barcodeId
     *
     * @param string $barcodeId
     *
     * @return Objekt
     */
    public function setBarcode($barcodeId)
    {
        $this->Barcode_id = $barcodeId;

        return $this;
    }

    /**
     * Get barcodeId
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->Barcode_id;
    }
    
    
    /**
     * Get barcodeId
     *
     * @return string
     */
    public function getBarcodeId()
    {
        return $this->Barcode_id;
    }
    

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Objekt
     */
    public function setName($name)
    {
        $this->Name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->Name;
    }

    /**
     * Set verwendung
     *
     * @param string $verwendung
     *
     * @return Objekt
     */
    public function setVerwendung($verwendung)
    {
        $this->Verwendung = $verwendung;

        return $this;
    }

    /**
     * Get verwendung
     *
     * @return string
     */
    public function getVerwendung()
    {
        return $this->Verwendung;
    }
    
    /**
     * Set notiz
     *
     * @param string $notiz
     *
     * @return Objekt
     */
    public function setNotiz($notiz)
    {
        $this->Notiz = $notiz;

        return $this;
    }

    /**
     * Get notiz
     *
     * @return string
     */
    public function getNotiz()
    {
        return $this->Notiz;
    }

    /**
     * Set zeitstempel
     *
     * @param \DateTime $zeitstempel
     *
     * @return Objekt
     */
    public function setZeitstempel($zeitstempel)
    {
        $this->Zeitstempel = $zeitstempel;

        return $this;
    }

    /**
     * Get zeitstempel
     *
     * @return \DateTime
     */
    public function getZeitstempel()
    {
        return $this->Zeitstempel;
    }
    
    
    
    /**
     * Set zeitstempel
     *
     * @param \DateTime $zeitstempel
     *
     * @return Objekt
     */
    public function setZeitstempelumsetzung($zeitstempel)
    {
        $this->Zeitstempelderumsetzung = $zeitstempel;

        return $this;
    }

    /**
     * Get zeitstempel
     *
     * @return \DateTime
     */
    public function getZeitstempelumsetzung()
    {
        return $this->Zeitstempelderumsetzung;
    }
    
    

    /**
     * Set Kategorie
     *
     * @param integer $id
     *
     * @return Objekt
     */
    public function setKategorie($id)
    {
        $this->Kategorie_id = $id;

        return $this;
    }

    /**
     * Get Kategorie
     *
     * @return integer
     */
    public function getKategorie()
    {
        return $this->Kategorie_id;
    }
    
    

    /**
     * Set nutzerId
     *
     * @param \AppBundle\Entity\Nutzer $nutzerId
     *
     * @return Objekt
     */
    public function setNutzer(\AppBundle\Entity\Nutzer $nutzerId)
    {
        $this->Nutzer_id = $nutzerId;
        return $this;
    }

    /**
     * Get nutzerId
     *
     * @return \AppBundle\Entity\Nutzer
     */
    public function getNutzer()
    {
        return $this->Nutzer_id;
    }
    
    
    
     /**
     * Set reserviertVon
     *
     * @param \AppBundle\Entity\Nutzer $nutzerId
     *
     * @return Historie_Objekt
     */
    public function setReserviertVon(\AppBundle\Entity\Nutzer $nutzerId = null)
    {
        $this->Reserviert_von = $nutzerId;

        return $this;
    }

    /**
     * Get reserviertVon
     *
     * @return \AppBundle\Entity\Nutzer
     */
    public function getreserviertVon()
    {
        return $this->Reserviert_von;
    }
    
    

    /**
     * Set fallId
     *
     * @param \AppBundle\Entity\Fall $fallId
     *
     * @return Objekt
     */
    public function setFall(\AppBundle\Entity\Fall $fallId = null)
    {
        $this->Fall_id = $fallId;

        return $this;
    }

    /**
     * Get fallId
     *
     * @return \AppBundle\Entity\Fall
     */
    public function getFall()
    {
        return $this->Fall_id;
    }

     /**
     * Set Status
     *
     * @param integer $id
     *
     * @return Objekt
     */
    public function setStatus($Id)
    {
        $this->Status_id = $Id;

        return $this;
    }

    /**
     * Get Status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->Status_id;
    }
    
    
     /**
     * Set Pic as Resource
     *
     * @param blob $id
     * @return Objekt
     */
    public function setPic($Id)
    {
        if($Id == "" || $Id == null){
            $this->Bild = null;
        }
        else{
            $strm = fopen($Id,"rb");
            $this->Bild = base64_encode(stream_get_contents($strm));
        }
        return $this;
    }

    /**
     * Get Pic as Resource
     *
     * @return string
     */
    public function getPic()
    {
        return $this->Bild;
    }
    
    
     /**
     * Set Picpath
     *
     * @param string $id
     *
     * @return Objekt
     */
    public function setPicpath($Id)
    {
        $this->BildPfad = $Id;

        return $this;
    }

    /**
     * Get Picpath
     *
     * @return string
     */
    public function getPicpath()
    {
        return $this->BildPfad;
    }
    

    /**
     * Set standort
     *
     * @param \AppBundle\Entity\Objekt $standort
     *
     * @return Objekt
     */
    public function setStandort(\AppBundle\Entity\Objekt $standort = null)
    {
        $this->Standort = $standort;

        return $this;
    }
    

    /**
     * Get standort
     *
     * @return \AppBundle\Entity\Objekt
     */
    public function getStandort()
    {
        return $this->Standort;
    }
    


    
    
    

    
    
    
    // https://afilina.com/doctrine-not-saving-manytomany
    // https://symfony.com/doc/current/doctrine/associations.html#associations-inverse-side
    public function addImage(Objekt $asservat){
        if($this->Kategorie_id != helper::KATEGORIE_DATENTRAEGER){
            return false;
        }
        else{
            $this->Images->add($asservat);
            $asservat->addHdd($this);
            return true;
        }
    }
    
    public function removeImage(Objekt $asservat){
        if($this->Kategorie_id != helper::KATEGORIE_DATENTRAEGER){
            return false;
        }
        else{
            $this->Images->removeElement($asservat);
            $asservat->removeHdd($this);
            return true;
        }
    }
    
    public function flushImages(){
        if($this->Kategorie_id != helper::KATEGORIE_DATENTRAEGER){
            return false;
        }
        else{
            
            foreach($this->Images as $datentraeger){
                $datentraeger->removeHdd($this);
            }
            $this->Images->clear();
            return true;
        }
    }
    
    
    
    public function getImages(){
        return $this->Images;
    }
    
    
    
    
    public function addHdd(Objekt $hdd){
        if($this->Kategorie_id != helper::KATEGORIE_ASSERVAT_DATENTRAEGER){
            return false;
        }
        else{
            $this->HDDs->add($hdd);
            return true;
        }
    }
    
    public function removeHdd(Objekt $hdd){
        if($this->Kategorie_id != helper::KATEGORIE_ASSERVAT_DATENTRAEGER){
            return false;
        }
        else{
            $this->HDDs->removeElement($hdd);
            return true;
        }
    }
    
    public function flushHdds(){
        if($this->Kategorie_id != helper::KATEGORIE_ASSERVAT_DATENTRAEGER){
            return false;
        }
        else{
            $this->HDDs->clear();
            return true;
        }
    }
    
    
    
    public function getHdds(){
        return $this->HDDs;
    }
    
    
    
    
    public function isUsable(){
        if($this->Status_id == helper::STATUS_VERLOREN ||
           $this->Status_id == helper::STATUS_VERNICHTET ){
            return false;
        }
        return true;
    }
    
    
}
