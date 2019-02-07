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
 * @ORM\Table(name="ams_Datentraeger")
 */
class Datentraeger
{
    public function __construct($info = null) {
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

    /**
     * @ORM\Column(type="string",length=9)
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Objekt")
     * @ORM\JoinColumn(name="reserviert_von",referencedColumnName="id",nullable=true)
     */
    protected $Barcode_id;

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $Formfaktor;
    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $Bauart;
    
    /**
     * @ORM\Column(type="integer",nullable=true)
     */
    protected $Groesse;
    
    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $Hersteller;
    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $Modell;
    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $SN;

    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $PN;
    
    
    /**
     * @ORM\Column(type="text",nullable=true)
     */
    protected $Anschluss;
    
    
    
    
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
     * Set barcodeId
     *
     * @param string $barcodeId
     *
     * @return Datentraeger
     */
    public function setBarcode($barcodeId)
    {
        $this->Barcode_id = $barcodeId;

        return $this;
    }
    
    
    
    /**
     * Get Bauart
     *
     * @return string
     */
    public function getBauart()
    {
        return $this->Bauart;
    }
    
    
    /**
     * Set Bauart
     *
     * @param string $Bauart
     *
     * @return Datentraeger
     */
    public function setBauart($Bauart)
    {
        $this->Bauart = $Bauart;

        return $this;
    }
    
    
    
    /**
     * Get Formfaktor
     *
     * @return string
     */
    public function getFormfaktor()
    {
        return $this->Formfaktor;
    }
    
    
    /**
     * Set Formfaktor
     *
     * @param string $Formfaktor
     *
     * @return Datentraeger
     */
    public function setFormfaktor($Formfaktor)
    {
        $this->Formfaktor = $Formfaktor;

        return $this;
    }
    
    
    /**
     * Get Groesse
     *
     * @return integer
     */
    public function getGroesse()
    {
        return $this->Groesse;
    }
    
    /**
     * Set Groesse
     *
     * @param integer $Groesse
     *
     * @return Datentraeger
     */
    public function setGroesse($Groesse)
    {
        $this->Groesse = $Groesse;

        return $this;
    }
    
    
     /**
     * Get Hersteller
     *
     * @return string
     */
    public function getHersteller()
    {
        return $this->Hersteller;
    }
    
    
    /**
     * Set Hersteller
     *
     * @param string $Hersteller
     *
     * @return Datentraeger
     */
    public function setHersteller($Hersteller)
    {
        $this->Hersteller = $Hersteller;

        return $this;
    }
    
    
    /**
     * Get Modell
     *
     * @return string
     */
    public function getModell()
    {
        return $this->Modell;
    }
    
    
    /**
     * Set Modell
     *
     * @param string $Modell
     *
     * @return Datentraeger
     */
    public function setModell($Modell)
    {
        $this->Modell = $Modell;

        return $this;
    }
    
    
    /**
     * Get SN
     *
     * @return string
     */
    public function getSN()
    {
        return $this->SN;
    }   
    
    
    /**
     * Set SN
     *
     * @param string $SN
     *
     * @return Datentraeger
     */
    public function setSN($SN)
    {
        $this->SN = $SN;

        return $this;
    }
    
    
    /**
     * Get PN
     *
     * @return string
     */
    public function getPN()
    {
        return $this->PN;
    } 
    
    /**
     * Set PN
     *
     * @param string $PN
     *
     * @return Datentraeger
     */
    public function setPN($PN)
    {
        $this->PN = $PN;

        return $this;
    }
    
    
    /**
     * Get Anschluss
     *
     * @return string
     */
    public function getAnschluss()
    {
        return $this->Anschluss;
    } 
    
    /**
     * Set Anschluss
     *
     * @param string $Anschluss
     *
     * @return Datentraeger
     */
    public function setAnschluss($Anschluss)
    {
        $this->Anschluss = $Anschluss;

        return $this;
    }
    
}
