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
#[ORM\Table(name: "ams_Datentraeger")]
class Datentraeger
{
    public function __construct($info = null) {
        if(null === $info) 
            return;
        $this->setBarcode($info['barcode_id']);
        $this->setBauart($info['bauart']);
        $this->setFormfaktor($info['formfaktor']);
        if($info['groesse'] != '' && $info['groessealt'] == ''){
            $this->setGroesse($info['groesse']);
        }
        else{
            $this->setGroesse($info['groessealt']);
        }
        $this->setHersteller($info['hersteller']);
        $this->setModell($info['modell']);
        $this->setSN($info['sn']);
        $this->setPN($info['pn']);
        $this->setAnschluss($info['anschluss']);
    }

    
    #[ORM\Column(type: "string",length:9)]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: "Objekt")]
    #[ORM\JoinColumn(name:"reserviert_von",referencedColumnName:"id",nullable:true)]
    protected $barcode_id;

    
    #[ORM\Column(type:"text",nullable:true)]
    protected $formfaktor;
    
    #[ORM\Column(type:"text",nullable:true)]
    protected $bauart;
    
    
    #[ORM\Column(type:"integer",nullable:true)]
    protected $groesse;
    
    
    #[ORM\Column(type:"text",nullable: true)]
    protected $hersteller;
    
    #[ORM\Column(type:"text",nullable: true)]
    protected $modell;
    
    #[ORM\Column(type:"text",nullable: true)]
    protected $sn;

    
    #[ORM\Column(type:"text",nullable: true)]
    protected $pn;
    
    
    #[ORM\Column(type:"text",nullable: true)]
    protected $anschluss;
    
    
    
    
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
     * Set barcodeId
     *
     * @param string $barcodeId
     *
     * @return Datentraeger
     */
    public function setBarcode($barcodeId)
    {
        $this->barcode_id = $barcodeId;

        return $this;
    }
    
    
    
    /**
     * Get bauart
     *
     * @return string
     */
    public function getBauart()
    {
        return $this->bauart;
    }
    
    
    /**
     * Set bauart
     *
     * @param string $bauart
     *
     * @return Datentraeger
     */
    public function setBauart($bauart)
    {
        $this->bauart = $bauart;

        return $this;
    }
    
    
    
    /**
     * Get formfaktor
     *
     * @return string
     */
    public function getFormfaktor()
    {
        return $this->formfaktor;
    }
    
    
    /**
     * Set formfaktor
     *
     * @param string $formfaktor
     *
     * @return Datentraeger
     */
    public function setFormfaktor($formfaktor)
    {
        $this->formfaktor = $formfaktor;

        return $this;
    }
    
    
    /**
     * Get groesse
     *
     * @return integer
     */
    public function getGroesse()
    {
        return $this->groesse;
    }
    
    /**
     * Set groesse
     *
     * @param integer $groesse
     *
     * @return Datentraeger
     */
    public function setGroesse($groesse)
    {
        $this->groesse = $groesse;

        return $this;
    }
    
    
     /**
     * Get hersteller
     *
     * @return string
     */
    public function getHersteller()
    {
        return $this->hersteller;
    }
    
    
    /**
     * Set hersteller
     *
     * @param string $hersteller
     *
     * @return Datentraeger
     */
    public function setHersteller($hersteller)
    {
        $this->hersteller = $hersteller;

        return $this;
    }
    
    
    /**
     * Get modell
     *
     * @return string
     */
    public function getModell()
    {
        return $this->modell;
    }
    
    
    /**
     * Set modell
     *
     * @param string $modell
     *
     * @return Datentraeger
     */
    public function setModell($modell)
    {
        $this->modell = $modell;

        return $this;
    }
    
    
    /**
     * Get sn
     *
     * @return string
     */
    public function getSN()
    {
        return $this->sn;
    }   
    
    
    /**
     * Set sn
     *
     * @param string $sn
     *
     * @return Datentraeger
     */
    public function setSN($sn)
    {
        $this->sn = $sn;

        return $this;
    }
    
    
    /**
     * Get pn
     *
     * @return string
     */
    public function getPN()
    {
        return $this->pn;
    } 
    
    /**
     * Set pn
     *
     * @param string $pn
     *
     * @return Datentraeger
     */
    public function setPN($pn)
    {
        $this->pn = $pn;

        return $this;
    }
    
    
    /**
     * Get anschluss
     *
     * @return string
     */
    public function getAnschluss()
    {
        return $this->anschluss;
    } 
    
    /**
     * Set anschluss
     *
     * @param string $anschluss
     *
     * @return Datentraeger
     */
    public function setAnschluss($anschluss)
    {
        $this->anschluss = $anschluss;

        return $this;
    }
    
}
