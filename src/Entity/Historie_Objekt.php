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

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "ams_Historie_Objekt")]
class Historie_Objekt
{
    
    #[ORM\Column(type:"integer")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy:"AUTO")]
    private $historie_id;
    
    
    #[ORM\Column(type:"string",length:9)]
    protected $barcode_id;

 
    #[ORM\Column(type:"datetime")]
    protected $zeitstempel;
    
    #[ORM\Column(type:"text",nullable:true)]
    protected $verwendung;
     
    
    #[ORM\ManyToOne(targetEntity:"Nutzer")]
    #[ORM\JoinColumn(name:"nutzer_id",referencedColumnName:"id",nullable:false)]
    protected $nutzer_id;
    
    
    #[ORM\ManyToOne(targetEntity:"Nutzer")]
    #[ORM\JoinColumn(name:"reserviert_von",referencedColumnName:"id",nullable:true)]
    protected $reserviert_von;
    
    
    
    #[ORM\ManyToOne(targetEntity:"Fall")]
    #[ORM\JoinColumn(name:"fall_id",referencedColumnName:"id")]
    protected $fall_id;
    
    
    #[ORM\Column(type:"integer")]
    protected $status_id;
    
    
    #[ORM\ManyToOne(targetEntity:"Objekt")]
    #[ORM\JoinColumn(name:"standort", referencedColumnName:"barcode_id")]
    protected $standort;
    
    
    
    #[ORM\Column(type:"datetime")]
    protected $zeitstempelderumsetzung;
    
    
    #[ORM\Column(type:"boolean")]
    protected $systemaktion = false;
    
    
    

    // Das hier ist eine One-To-Many Relation, um die Images der gespeicherten
    // Objekte vernuenftig speichern zu koennen.
   

    #[ORM\ManyToMany(targetEntity:"Objekt")]
    #[ORM\JoinTable(name:"ams_image_objekt")]
    #[ORM\JoinColumn(name:"historie_id", referencedColumnName:"historie_id")]
    #[ORM\InverseJoinColumn(name:"barcode_id", referencedColumnName:"barcode_id")]    
    private $images;

    
    public function __construct($barcode_id) {
        $this->barcode_id = $barcode_id;
        
        $this->images= new \Doctrine\Common\Collections\ArrayCollection();
        $this->zeitstempel = new \DateTime();
    
    }
    

    /**
     * Set barcodeId
     *
     * @param string $barcodeId
     *
     * @return Historie_Objekt
     */
    public function setBarcodeId($barcodeId)
    {
        $this->barcode_id = $barcodeId;

        return $this;
    }

    /**
     * Get barcodeId
     *
     * @return string
     */
    public function getBarcodeId()
    {
        return $this->barcode_id;
    }
    //WORKAROUND: TO FIX:
    public function getBarcode()
    {
        return $this->barcode_id;
    }

    /**
     * Set zeitstempel
     *
     * @param \DateTime $zeitstempel
     *
     * @return Historie_Objekt
     */
    public function setZeitstempel(\Datetime $zeitstempel)
    {
        $this->zeitstempel = $zeitstempel;

        return $this;
    }

    /**
     * Get zeitstempel
     *
     * @return \DateTime
     */
    public function getZeitstempel()
    {
        return $this->zeitstempel;
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
        $this->zeitstempelderumsetzung = $zeitstempel;

        return $this;
    }

    /**
     * Get zeitstempel
     *
     * @return \DateTime
     */
    public function getZeitstempelumsetzung()
    {
        return $this->zeitstempelderumsetzung;
    }
    
    
    

    /**
     * Set verwendung
     *
     * @param string $verwendung
     *
     * @return Historie_Objekt
     */
    public function setVerwendung($verwendung)
    {
        $this->verwendung = $verwendung;

        return $this;
    }

    /**
     * Get verwendung
     *
     * @return string
     */
    public function getVerwendung()
    {
        return $this->verwendung;
    }

    /**
     * Set nutzerId
     *
     * @param \App\Entity\Nutzer $nutzerId
     *
     * @return Historie_Objekt
     */
    public function setNutzerId(\App\Entity\Nutzer $nutzerId)
    {
        $this->nutzer_id = $nutzerId;

        return $this;
    }

    /**
     * Get nutzerId
     *
     * @return \App\Entity\Nutzer
     */
    public function getNutzerId()
    {
        return $this->nutzer_id;
    }
    
    
    
    /**
     * Set reserviertVon
     *
     * @param \App\Entity\Nutzer $nutzerId
     *
     * @return Historie_Objekt
     */
    public function setReserviertVon(\App\Entity\Nutzer $nutzerId = null)
    {
        $this->reserviert_von = $nutzerId;

        return $this;
    }

    /**
     * Get reserviertVon
     *
     * @return \App\Entity\Nutzer
     */
    public function getReserviertVon()
    {
        return $this->reserviert_von;
    }

    
    

    /**
     * Set fallId
     *
     * @param \App\Entity\Fall $fallId
     *
     * @return Historie_Objekt
     */
    public function setFall(\App\Entity\Fall $fallId = null)
    {
        $this->fall_id = $fallId;

        return $this;
    }

    /**
     * Get fallId
     *
     * @return \App\Entity\Fall
     */
    public function getFall()
    {
        return $this->fall_id;
    }

    /**
     * Set statusId
     * @param integer $id
     * @return Historie_Objekt
     */
    public function setStatusId( $id)
    {
        $this->status_id = $id;

        return $this;
    }

    /**
     * Get statusId
     *
     * @return integer
     */
    public function getStatusId()
    {
        return $this->status_id;
    }

    /**
     * Set standort
     *
     * @param \App\Entity\Objekt $standort
     *
     * @return Historie_Objekt
     */
    public function setStandort(\App\Entity\Objekt $standort = null)
    {
        $this->standort = $standort;

        return $this;
    }

    /**
     * Get standort
     *
     * @return \App\Entity\Objekt
     */
    public function getStandort()
    {
        return $this->standort;
    }
    
    public function setSystemaktion($status){
        $this->systemaktion = $status;
    }
    
    public function GetSystemaktion(){
        return $this->systemaktion;
    }
   
    
    public function addImage(\App\Entity\Objekt $object){
        $this->images->add($object);
    }
    
    public function getImages(){
        return $this->images;
    }
    
}
