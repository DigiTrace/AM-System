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

use App\Repository\ObjektRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Controller\helper;
use Symfony\Component\Validator\Constraints as Assert;



#[ORM\Entity(repositoryClass: ObjektRepository::class)]
#[ORM\Table(name: "ams_Objekt")]
class Objekt
{
    const KATEGORIE_ASSERVAT = 0;
    const KATEGORIE_AUSRUESTUNG = 1;
    const KATEGORIE_BEHAELTER = 2;
    const KATEGORIE_DATENTRAEGER = 3;
    const KATEGORIE_AKTE = 4;
    const KATEGORIE_ASSERVAT_DATENTRAEGER = 5;
    
    
    
    const STATUS_EINGETRAGEN = 0;
    const STATUS_GENULLT = 1;
    const STATUS_ZUM_KUNDEN_MITGENOMMEN = 2;
    const STATUS_VERNICHTET = 3;
    const STATUS_AN_PERSON_UEBERGEBEN = 4;
    const STATUS_RESERVIERT = 5;
    const STATUS_VERLOREN = 6;
    const STATUS_IN_EINEM_BEHAELTER_GELEGT = 7;
    const STATUS_AUS_DEM_BEHAELTER_ENTFERNT = 8;
    const STATUS_EINEM_FALL_HINZUGEFUEGT = 9;
    const STATUS_AUS_DEM_FALL_ENTFERNT = 10;
    const STATUS_RESERVIERUNG_AUFGEHOBEN = 11;
    const STATUS_IN_VERWENDUNG = 12;
    const STATUS_EDITIERT = 13;
    const STATUS_FESTPLATTENIMAGE_GESPEICHERT = 14;
    
    
    const VSTATUS_NEUTRALISIERT = 40;
    
    
    static $statusToId = array( 
        'status.added'            => Objekt::STATUS_EINGETRAGEN,
        'status.cleaned'                 => Objekt::STATUS_GENULLT, 
        'status.taken.to.customer'       => Objekt::STATUS_ZUM_KUNDEN_MITGENOMMEN,
        'status.destroyed'               => Objekt::STATUS_VERNICHTET,
        'status.handover.person'         => Objekt::STATUS_AN_PERSON_UEBERGEBEN,
        'status.reserved'                => Objekt::STATUS_RESERVIERT,
        'status.lost'                    => Objekt::STATUS_VERLOREN,
        'status.stored.in.container'     => Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT,
        'status.pulled.out.of.container' => Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT,
        'status.added.to.case'           => Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT,
        'status.removed.from.case'       => Objekt::STATUS_AUS_DEM_FALL_ENTFERNT,
        'status.unbind.reservation'      => Objekt::STATUS_RESERVIERUNG_AUFGEHOBEN,
        'status.used'                    => Objekt::STATUS_IN_VERWENDUNG,
        'status.edited'                  => Objekt::STATUS_EDITIERT,
        'status.saved.image'             => Objekt::STATUS_FESTPLATTENIMAGE_GESPEICHERT);
    
    
    static $kategorienToId = array( 
        'category.exhibit'     => Objekt::KATEGORIE_ASSERVAT,
        'category.equipment'   => Objekt::KATEGORIE_AUSRUESTUNG, 
        'category.container'   => Objekt::KATEGORIE_BEHAELTER,
        'category.hdd'         => Objekt::KATEGORIE_DATENTRAEGER,
        'category.record'      => Objekt::KATEGORIE_AKTE,
        'category.exhibit.hdd' => Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER);
    
    
    
    static $vstatusToId = array ("status.neutralize" => helper::VSTATUS_NEUTRALISIERT);
    
    private $translator;

    public function __construct() {
        $this->zeitstempel = new \DateTime();
        $this->zeitstempelderumsetzung = new \DateTime();
        
        $this->HDDs = new \Doctrine\Common\Collections\ArrayCollection();
        $this->Images = new \Doctrine\Common\Collections\ArrayCollection();
        
    }

    #[ORM\Column(type: "string",length: 9)]
    #[ORM\Id]
    #[ORM\OneToMany(targetEntity:"Objekt",mappedBy:"standort")]
    protected $barcode_id;

    
    #[ORM\Column(type: "text")]
    protected $name;
    
    
    #[ORM\Column(type: "text",nullable: true)]
    protected $verwendung;
    
    #[ORM\Column(type: "text",nullable: true)]
    protected $notiz;
    
    
    #[ORM\Column(type: "integer")]
    protected $kategorie_id;
    
    #[ORM\ManyToOne(targetEntity: "Nutzer")]
    #[ORM\JoinColumn(name: "nutzer_id",referencedColumnName: "id",nullable: false)]
    protected $nutzer_id;
    
    
    #[ORM\ManyToOne(targetEntity: "Nutzer")]
    #[ORM\JoinColumn(name:"reserviert_von",referencedColumnName:"id",nullable: true)]
    protected $reserviert_von;
    
     
    #[ORM\ManyToOne(targetEntity: "Fall", inversedBy: "objekte")]
    #[ORM\JoinColumn(name:"fall_id",referencedColumnName:"id")]
    protected $fall_id;
    
    #[ORM\Column(type: "integer")]
    protected $status_id;
    
    #[ORM\Column(type: "boolean",nullable: true)]
    protected $systemaktion = false;
    
    
    #[ORM\ManyToOne(targetEntity: "Objekt")]
    #[ORM\JoinColumn(name:"standort", referencedColumnName:"barcode_id")]
    protected $standort;
    
    
    #[ORM\Column(type: "datetime")]
    protected $zeitstempel;
    
    
    #[ORM\Column(type: "datetime")]
    protected $zeitstempelderumsetzung;
    
   
     
    
    // Viele Asservate sind in vielen HDDs gespeichert
    #[ORM\ManyToMany(targetEntity: "Objekt", mappedBy: "HDDs")]
    private $Images;
    
    
    
    // Viele HDDs haben viele Asservate

    #[ORM\ManyToMany(targetEntity: "Objekt", inversedBy: "Images")]
    #[ORM\JoinTable(name: "ams_ZuordnungImageToHDD")]
    #[ORM\JoinColumn(name: "image", referencedColumnName: "barcode_id")]
    #[ORM\InverseJoinColumn(name: "hdd", referencedColumnName: "barcode_id")]
    private $HDDs;
    
    
    #[ORM\OneToOne(targetEntity: "ObjektBlob", mappedBy:"objekt",cascade: ["persist", "remove"])]
    protected $objektBlob;
    
    

    
  
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
        $this->barcode_id = $barcodeId;

        return $this;
    }

    /**
     * Get barcodeId
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode_id;
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
    

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Objekt
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Set notiz
     *
     * @param string $notiz
     *
     * @return Objekt
     */
    public function setNotiz($notiz)
    {
        $this->notiz = $notiz;

        return $this;
    }

    /**
     * Get notiz
     *
     * @return string
     */
    public function getNotiz()
    {
        return $this->notiz;
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
     * Set Kategorie
     *
     * @param integer $id
     *
     * @return Objekt
     */
    public function setKategorie($id)
    {
        $this->kategorie_id = $id;

        return $this;
    }

    /**
     * Get Kategorie
     *
     * @return integer
     */
    public function getKategorie()
    {
        return $this->kategorie_id;
    }
    
    
    /**
     * Get Kategorie
     *
     * @return string
     */
    public function getKategorieName()
    {
        return $this->getKategorieNameFromId($this->kategorie_id);
    }
    
    

    /**
     * Set nutzerId
     *
     * @param \App\Entity\Nutzer $nutzerId
     *
     * @return Objekt
     */
    public function setNutzer(\App\Entity\Nutzer $nutzerId)
    {
        $this->nutzer_id = $nutzerId;
        return $this;
    }

    /**
     * Get nutzerId
     *
     * @return \App\Entity\Nutzer
     */
    public function getNutzer()
    {
        return $this->nutzer_id;
    }
    
    
    
     /**
     * Set reserviertVon
     *
     * @param \App\Entity\Nutzer $nutzerId
     *
     * @return HistorieObjekt
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
    public function getreserviertVon()
    {
        return $this->reserviert_von;
    }
    
    

    /**
     * Set fallId
     *
     * @param \App\Entity\Fall $fallId
     *
     * @return Objekt
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
     * Set Status
     *
     * @param integer $id
     *
     * @return Objekt
     */
    public function setStatus($Id)
    {
        $this->status_id = $Id;

        return $this;
    }

    /**
     * Get Status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status_id;
    }
    
    
    /**
     * Get Status
     *
     * @return string
     */
    public function getStatusName()
    {
        return $this->getStatusNameFromId($this->status_id);
    }
    
     /**
     * Set Pic as Resource
     *
     * @param blob $id
     * 
     */
    public function setPic($Id)
    {
        if($this->objektBlob == null){
            $this->objektBlob = new ObjektBlob($this);
        }
        
        $this->objektBlob->setPic($Id);
    }

    /**
     * Get Pic as Resource
     *
     * @return string
     */
    public function getPic()
    {
        if($this->objektBlob != null){
            return $this->objektBlob->getPic();
        }
        return null;
    }
    
    
     /**
     * Set Picpath
     *
     * @param string $id
     */
    public function setPicpath($Id)
    {
        if($this->objektBlob == null){
            $this->objektBlob = new ObjektBlob($this);
        }
        
        $this->objektBlob->setPicpath($Id);
    }

    /**
     * Get Picpath
     *
     * @return string
     */
    public function getPicpath()
    {
        if($this->objektBlob != null){
            return $this->objektBlob->getPicpath();
        }
        return null;
    }
    

    /**
     * Set standort
     *
     * @param \App\Entity\Objekt $standort
     *
     * @return Objekt
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
    
    
    // https://afilina.com/doctrine-not-saving-manytomany
    // https://symfony.com/doc/current/doctrine/associations.html#associations-inverse-side
    public function addImage(Objekt $asservat){
        if($this->kategorie_id != helper::KATEGORIE_DATENTRAEGER){
            return false;
        }
        else{
            $this->Images->add($asservat);
            $asservat->addHdd($this);
            return true;
        }
    }
    
    public function removeImage(Objekt $asservat){
        if($this->kategorie_id != helper::KATEGORIE_DATENTRAEGER){
            return false;
        }
        else{
            $this->Images->removeElement($asservat);
            $asservat->removeHdd($this);
            return true;
        }
    }
    
    public function flushImages(){
        if($this->kategorie_id != helper::KATEGORIE_DATENTRAEGER){
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
        if($this->kategorie_id != helper::KATEGORIE_ASSERVAT_DATENTRAEGER){
            return false;
        }
        else{
            $this->HDDs->add($hdd);
            return true;
        }
    }
    
    public function removeHdd(Objekt $hdd){
        if($this->kategorie_id != helper::KATEGORIE_ASSERVAT_DATENTRAEGER){
            return false;
        }
        else{
            $this->HDDs->removeElement($hdd);
            return true;
        }
    }
    
    public function flushHdds(){
        if($this->kategorie_id != helper::KATEGORIE_ASSERVAT_DATENTRAEGER){
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
        if($this->status_id == helper::STATUS_VERLOREN ||
           $this->status_id == helper::STATUS_VERNICHTET ){
            return false;
        }
        return true;
    }
    
    // Get the count of avaiable Categories in the Objekt Entity
    // Must be incremented, 
    public static function getCountCategories(){
        return 6;
    }
    
    // Get the count of avaiable Categories in the Objekt Entity
    // Must be incremented, 
    public static function getCountStatues(){
        return 15;
    }
    
    public static function getKategorieNameFromId($categoryid){
        foreach(Objekt::$kategorienToId as $key => $value){
            if($value == $categoryid)
                return $key;
        }
    }
    
    public static function getStatusNameFromId($statusid){
        foreach(Objekt::$statusToId as $key => $value){
            if($value == $statusid)
                return $key;
        }
    }
    
    public function setSystemaktion($status){
        $this->systemaktion = $status;
    }
    
    public function GetSystemaktion(){
        return $this->systemaktion;
    }
    
    

    public function isObjectWithNewStatusValid( $newstatus, 
                                                $contextparameter = null, 
                                                &$reason = null){
        $valid = true;
        $contextreason = "";
        //  Die Vererbung der Aktion zu den eingelagerten Objekten
        //  sind zu fehleranfaellig. Funktionalitaet wurde entfernt
        /* if($this->getKategorie() == Objekt::KATEGORIE_BEHAELTER){
            $valid = false;
            $reason = "container_be_used_for_mass_update";
        }*/
        
        if($newstatus == $this->getStatus() &&
           !($this->getStatus() == Objekt::STATUS_IN_VERWENDUNG ||
             $this->getStatus() == Objekt::STATUS_FESTPLATTENIMAGE_GESPEICHERT || 
             $this->getStatus() == Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT )
          ){
            $valid = false;
            $reason = "object_already_in_this_status";
        }
        
        
        if($newstatus == Objekt::STATUS_GENULLT && 
            $this->getKategorie() != Objekt::KATEGORIE_DATENTRAEGER ){
                $valid = false;
                $reason = "object_is_not_a_hdd";
        }
        
        if($newstatus == Objekt::STATUS_FESTPLATTENIMAGE_GESPEICHERT && 
            $this->getKategorie() != Objekt::KATEGORIE_DATENTRAEGER){
            
            
            if($this->getKategorie() != Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER){
                $valid = false;
                $reason = "object_is_not_a_hdd";
            }
        }

        if($this->getStatus() == Objekt::STATUS_VERLOREN ||
           $this->getStatus() == Objekt::STATUS_VERNICHTET){
            $valid = false;
            $reason = "object_is_destroyed_or_lost";
        }

        if($newstatus == Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT &&
                $this->getStandort() == null){
            $valid = false;
            $reason = "object_is_not_stored";
        }
        
        if($newstatus == Objekt::STATUS_RESERVIERUNG_AUFGEHOBEN &&
                $this->getreserviertVon() == null){
            $valid = false;
            $reason = "object_is_not_reserved";
        }
        
        if($newstatus == Objekt::STATUS_RESERVIERT &&
                $this->getreserviertVon() != null){
            $valid = false;
            $reason = "object_is_already_reserved";
        }
        

        if($newstatus == Objekt::STATUS_AUS_DEM_FALL_ENTFERNT){
            
            if($this->getFall() == null){
                $valid = false;
                $reason = "object_is_not_in_a_case";
            }
            
            if( $this->getKategorie() == Objekt::KATEGORIE_AKTE){
                $valid = false;
                $reason = "records_cant_be_removed_from_case";
            }
        }
        
        if($newstatus == Objekt::VSTATUS_NEUTRALISIERT &&
                $this->getKategorie() != Objekt::KATEGORIE_DATENTRAEGER){
           
            
                $valid = false;
                $reason = "action_can_not_be_done_by_object";
        }
        
        
        

       /* if($store_object != null){
            if($this->has_object_relationship_with_store_object($this, $store_object) ||
                $this->getStandort() != null){
                $errorActionOnObject = $errorActionOnObject . $id."\r\n";
            }
        }

        if($case != null){
            if($this->getFall() != null){
                $errorActionOnObject = $errorActionOnObject . $id."\r\n";
            }
        }*/

        if($contextparameter != null){
            // when a Object has to be stored
            if($contextparameter instanceof \App\Entity\Objekt &&
                  $newstatus == Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT){
                
                
                if($contextparameter->getKategorie() != Objekt::KATEGORIE_BEHAELTER){
                    $valid = false;
                    $reason = "object_is_no_container";
                }
                if($this->has_object_relationship_with_store_object($contextparameter)){
                        
                    $valid = false;
                    $reason = "object_has_relationship_with_stored_object";
                }
                
                // This condition is disabled due to enable lazy swap
                /*if($this->getStandort() != null){
                    $valid = false;
                    $reason = "object_is_stored_in_another_object %context%";
                    $contextreason = $this->getStandort()." | ".$this->getStandort()->getName();
                    
                }*/
                
                // Container has to be also valid
                
                if($contextparameter->getStatus() == Objekt::STATUS_VERNICHTET ||
                   $contextparameter->getStatus() == Objekt::STATUS_VERLOREN){
                    $valid = false;
                    $reason = "to_be_added_container_is_destroyed_or_lost";
                }
                
                
            }
            
            
            // when a image has to stored in HDD
            if($contextparameter instanceof \App\Entity\Objekt &&
                  $newstatus == Objekt::STATUS_FESTPLATTENIMAGE_GESPEICHERT){
                
                
                if($contextparameter->getKategorie() != Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER){
                    $valid = false;
                    $reason = "object_is_no_exhibit_hdd";
                }
                
                if($this->getImages()->contains($contextparameter)){
                    $valid = false;
                    
                    $reason = "image_is_already_in_hdd %context%";
                    $contextreason = $contextparameter->getBarcode()." | ".$contextparameter->getName();
                    
                }
                
                // Container has to be also valid
                
                if($contextparameter->getStatus() == Objekt::STATUS_VERNICHTET ||
                   $contextparameter->getStatus() == Objekt::STATUS_VERLOREN){
                    $valid = false;
                    $reason = "exhibit_hdd_is_destroyed_or_lost";
                }
                
                
            }
            

            if($contextparameter instanceof \App\Entity\Fall){
                if($this->getFall() != null){
                    $valid = false;
                    $reason = "object_is_already_in_a_other_case %context%";
                    $contextreason = $this->getFall()->getId();
                }
            }

        }
        
        return $valid;
    }


    // Überprüfung, ob das einzulagernde Objekt bereits mit den Behaeltern
    // in einer Weise verbunden sind
    public function has_object_relationship_with_store_object(\App\Entity\Objekt $store_object){
        // Ein Objekt darf sich nicht selbst einlagern duerfen
        if($this->getBarcode() == $store_object->getBarcode()){
            return true;
        }
        
        if($this->getStandort() != null){
            if($this->getStandort()->getBarcode() == 
                 $store_object->getBarcode()){
                return true;
            }
        }
        
        $temp_object = clone $store_object;
        while($temp_object->getStandort() != null){
            if($temp_object->getStandort()->getBarcode() == $this->getBarcode()){
                return true;
            }
            else{
                $temp_object = $temp_object->getStandort();
            }
        }
        return false;
    }


    /*
     * Diese Funktion generiert einen Historieneintrag aus den derzeitigen
     * Informationen des Objektes. DIES MUSS BEI JEDER AENDERUNG DES OBJEKTES
     * AUSGEFUEHRT WERDEN
     */
    public function createNewHistorieEntry(){  
        $hist = new \App\Entity\HistorieObjekt($this->getBarcode());
        
        $hist->setFall($this->getFall());
        $hist->setNutzerId($this->getNutzer());
        $hist->setReserviertVon($this->getreserviertVon());
        $hist->setStandort($this->getStandort());
        $hist->setStatusId($this->getStatus());
        $hist->setVerwendung($this->getVerwendung());
        $hist->setZeitstempel($this->getZeitstempel());
        $hist->setZeitstempelumsetzung($this->getZeitstempelumsetzung());
        $hist->setSystemaktion($this->GetSystemaktion());
        
        
        foreach($this->getImages() as $image){
            $hist->addImage($image);
        }
        return $hist;
    }

}
