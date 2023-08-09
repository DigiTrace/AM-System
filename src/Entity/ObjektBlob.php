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
use App\Controller\helper;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: "ams_ObjektBlob")]
class ObjektBlob
{
    
    /**
     * Set Barcode_ID
     *
     * @param Objekt $o
     */
    public function __construct($o) {
        $this->Objekt = $o;
    }

    
    
    #[ORM\OneToOne(targetEntity: "Objekt",inversedBy: "ObjektBlob")]
    #[ORM\Id]
    #[ORM\JoinColumn(name:"barcode_id",referencedColumnName:"barcode_id",nullable: false)]
    protected $Objekt;
    
   
    
    #[ORM\Column(type: "text",nullable: true)]
    #[Assert\File(mimeTypes: [ "image/jpeg" ])]
    protected $Bild;
    
    
    #[ORM\Column(type:"string",nullable:true)]
    #[Assert\File(mimeTypes: [ "image/jpeg" ])]
    private $BildPfad;
    
    
    /**
     * Get barcodeId
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->Objekt->getBarcodeId();
    }
  
    public function __toString()
    {
      return $this->getBarcode(); // if you have a name property you can do $this->getName();
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
    
    
}
