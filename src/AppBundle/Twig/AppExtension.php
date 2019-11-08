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

namespace AppBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AppExtension extends AbstractExtension
{
    private $generator;

    public function __construct(UrlGeneratorInterface $generator)
    {
      $this->generator = $generator;
    }
    
    
    public function getFilters()
    {
        return array(
            new TwigFilter('barcodelinker', array($this, 'barcodeLinker')),
            new TwigFilter('caselinker', array($this, 'caseLinker')),
            new TwigFilter('wrappergetStatusNameFromId', array($this, 'wrappergetStatusNameFromId')),
        );
    }

    public function barcodeLinker($text)
    {   // Matches "DTHW00000 " -> OUTPUT "DTHW00000"
        $pattern = "/(?J)(?<barcode>(DT(HW|AS|HD|AK))\d{5})+/";
        
        
        
        $text = preg_replace_callback(
            $pattern,
            function ($match) {
              return '<a href="'.$this->generator->generate("detail_object",array("id"=>$match["barcode"])).'">'.$match["barcode"]."</a>";
            },
            $text
          );
        
        return $text;
    }
    
    
    public function caseLinker($text)
    {   
        $pattern = "/(?J)(?<case>(DTFA)\d{0,8})+/";
        
        
        
        $text = preg_replace_callback(
            $pattern,
            function ($match) {
              return '<a href="'.$this->generator->generate("detail_case",array("id"=>$match["case"])).'">'.$match["case"]."</a>";
            },
            $text
          );
        
        return $text;
    }
    // Workaround because static functions cant be directly called in Twig
    public function wrappergetStatusNameFromId($text)
    {   
        return \AppBundle\Entity\Objekt::getStatusNameFromId($text);
    }
    
    
}





