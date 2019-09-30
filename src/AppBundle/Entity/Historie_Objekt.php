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
/**
 * @ORM\Entity
 * @ORM\Table(name="ams_Historie_Objekt")
 */
class Historie_Objekt
{
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $historie_id;
    
    
    /**
     * @ORM\Column(type="string",length=9)
     * 
     */
    protected $Barcode_id;

    
     /**
      *  
     * @ORM\Column(type="datetime")
     */
    protected $Zeitstempel;
    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $Verwendung;
     
    
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
     * @ORM\ManyToOne(targetEntity="Fall")
     * @ORM\JoinColumn(name="fall_id",referencedColumnName="id")
     */
    protected $Fall_id;
    
     /**
     * @ORM\Column(type="integer")
     */
    protected $Status_id;
    
      /**
     * @ORM\ManyToOne(targetEntity="Objekt")
     * @ORM\JoinColumn(name="standort", referencedColumnName="barcode_id")
     */
    protected $Standort;
    
    
    /**
     * @ORM\Column(type="datetime")
     */
    protected $Zeitstempelderumsetzung;
    
    
    /**
     * 
     * @ORM\Column(type="boolean")
     */
    protected $Systemaktion = false;
    
    
    
    /**
     *  Das hier ist eine One-To-Many Relation, um die Images der gespeicherten
     *  Objekte vernuenftig speichern zu koennen.
     * @ORM\ManyToMany(targetEntity="Objekt")
     * @ORM\JoinTable(name="ams_image_objekt",
     *      joinColumns={@ORM\JoinColumn(name="historie_id", referencedColumnName="historie_id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="Barcode_id", referencedColumnName="barcode_id")}
     *      )
     */
    private $images;

    
    public function __construct($barcode_id) {
        $this->Barcode_id = $barcode_id;
        
        $this->images= new \Doctrine\Common\Collections\ArrayCollection();
        $this->Zeitstempel = new \DateTime();
    
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
        $this->Barcode_id = $barcodeId;

        return $this;
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
     * Set zeitstempel
     *
     * @param \DateTime $zeitstempel
     *
     * @return Historie_Objekt
     */
    public function setZeitstempel(\Datetime $zeitstempel)
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
     * Set verwendung
     *
     * @param string $verwendung
     *
     * @return Historie_Objekt
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
     * Set nutzerId
     *
     * @param \AppBundle\Entity\Nutzer $nutzerId
     *
     * @return Historie_Objekt
     */
    public function setNutzerId(\AppBundle\Entity\Nutzer $nutzerId)
    {
        $this->Nutzer_id = $nutzerId;

        return $this;
    }

    /**
     * Get nutzerId
     *
     * @return \AppBundle\Entity\Nutzer
     */
    public function getNutzerId()
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
    public function getReserviertVon()
    {
        return $this->Reserviert_von;
    }

    
    

    /**
     * Set fallId
     *
     * @param \AppBundle\Entity\Fall $fallId
     *
     * @return Historie_Objekt
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
     * Set statusId
     * @param integer $id
     * @return Historie_Objekt
     */
    public function setStatusId( $id)
    {
        $this->Status_id = $id;

        return $this;
    }

    /**
     * Get statusId
     *
     * @return integer
     */
    public function getStatusId()
    {
        return $this->Status_id;
    }

    /**
     * Set standort
     *
     * @param \AppBundle\Entity\Objekt $standort
     *
     * @return Historie_Objekt
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
    
   
    
    public function addImage(\AppBundle\Entity\Objekt $object){
        $this->images->add($object);
    }
    
    public function getImages(){
        return $this->images;
    }
    
}
