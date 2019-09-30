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
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="ams_Fall")
 */
class Fall
{
    public function __construct() {
        $time = new \DateTime('NOW');
        $this->Zeitstempel_beginn = $time;
    }
    
    
    
    // Da Fall_IDs als Primaerschluessel sich als ungeeignet gezeigt haben,
    // muessen die bestehenden Daten migriert werden, wobei das hier lazy stattfindet
    /**
     * 
     * ORM\Column(type="integer",nullable=true)
     */
    //protected $newid;

    /**
     * 
     * @ORM\Column(type="boolean")
     */
    protected $istAktiv = true;
    
    
    /**
     * @ORM\Column(name="id",type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */    
    protected $id;
    
    public function getId(){
        return $this->id;
    }
    
    public function setId($newid){
        $this->id =$newid;
    }
    
    /**
     * @ORM\Column(type="string",length=255)
     * @Assert\NotBlank()
     */    
    protected $case_id;
    
    
    public function getCaseId(){
        return $this->case_id;
    }
    
    public function setCaseId($caseid){
        $this->case_id = $caseid;
    }
    
    
    
    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    protected $Beschreibung;
    
    
    /**
     * @ORM\Column(type="datetime")
     */
    protected $Zeitstempel_beginn;

    
    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    protected $Zeitstempel_ende;

  

    /**
     * @ORM\OneToMany(targetEntity="Objekt", mappedBy="Fall_id")
     */
    protected $objekte;
    
    
    
    /**
     * Add objekt
     *
     * @param \AppBundle\Entity\Objekt $objekt
     *
     * @return Fall
     */
    public function addObjekt(\AppBundle\Entity\Objekt $objekt)
    {
        $this->objekte[] = $objekt;

        return $this;
    }

    /**
     * Remove objekt
     *
     * @param \AppBundle\Entity\Objekt $objekt
     */
    public function removeObjekt(\AppBundle\Entity\Objekt $objekt)
    {
        $this->objekte->removeElement($objekt);
    }
    
    
    
    public function getObjekte(){
        return $this->objekte;
    }

    
    
    public function getBeschreibung() {
        return $this->Beschreibung;
    }
        
    public function getZeitstempel(){
        return $this->Zeitstempel_beginn;
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
        return $this->Fall_id;
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
        $this->Beschreibung = $beschreibung;

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
        $this->Zeitstempel_beginn = $zeitstempel;

        return $this;
    }
    
    
}
