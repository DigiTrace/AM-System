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

use App\Repository\FallRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;


#[ORM\Entity(repositoryClass: FallRepository::class)]
#[ORM\Table(name: "ams_Fall")]
class Fall
{
    // DOS -> degree of secrecy
    const DEGREE_OF_SECRECY_PUBLIC = "DOS_PUBLIC";
    const DEGREE_OF_SECRECY_INTERNAL = "DOS_INTERNAL";
    const DEGREE_OF_SECRECY_CONFIDENTIAL = "DOS_CONFIDENTIAL";
    const DEGREE_OF_SECRECY_SECRET = "DOS_SECRET";
    
    
    public function __construct() {
        $time = new \DateTime('NOW');
        $this->zeitstempel_beginn = $time;
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
    protected $DOS = Fall::DEGREE_OF_SECRECY_PUBLIC;
    
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

  

    
    #[ORM\OneToMany(targetEntity: "Objekt", mappedBy: "fall_id")]
    protected $objekte;
    
    
    
    /**
     * Add objekt
     *
     * @param \App\Entity\Objekt $objekt
     *
     * @return Fall
     */
    public function addObjekt(\App\Entity\Objekt $objekt)
    {
        $this->objekte[] = $objekt;

        return $this;
    }

    /**
     * Remove objekt
     *
     * @param \App\Entity\Objekt $objekt
     */
    public function removeObjekt(\App\Entity\Objekt $objekt)
    {
        $this->objekte->removeElement($objekt);
    }
    
    
    
    public function getObjekte(){
        return $this->objekte;
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
     * @return Array
     */
    static function getDOSList()
    {
        return array(Fall::DEGREE_OF_SECRECY_PUBLIC,
                    Fall::DEGREE_OF_SECRECY_INTERNAL,
                    Fall::DEGREE_OF_SECRECY_CONFIDENTIAL,
                    Fall::DEGREE_OF_SECRECY_SECRET);
    }
    
    
    
}
