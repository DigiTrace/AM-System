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
   

namespace App\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class helper {
    
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
    
    
    // Virtual status which cant be used for real status
    
    const VSTATUS_NEUTRALISIERT = 40;


    const KATEGORIE_ASSERVAT = 0;
    const KATEGORIE_AUSRUESTUNG = 1;
    const KATEGORIE_BEHAELTER = 2;
    const KATEGORIE_DATENTRAEGER = 3;
    const KATEGORIE_AKTE = 4;
    const KATEGORIE_ASSERVAT_DATENTRAEGER = 5;
    
    // Important Note: Edit also base.html.twig
    static $kategorienToId = array( 'category.exhibit' => helper::KATEGORIE_ASSERVAT,
                         'category.equipment' =>   helper::KATEGORIE_AUSRUESTUNG, 
                         'category.container' =>   helper::KATEGORIE_BEHAELTER,
                         'category.hdd' =>         helper::KATEGORIE_DATENTRAEGER,
                         'category.record' =>      helper::KATEGORIE_AKTE,
                         'category.exhibit.hdd' => helper::KATEGORIE_ASSERVAT_DATENTRAEGER);
    
    
    static $statusToId = array( 'status.added' =>0,
                         'status.cleaned' => 1, 
                         'status.taken.to.customer' => 2,
                         'status.destroyed' => 3,
                         'status.handover.person' => 4,
                         'status.reserved' => 5,
                         'status.lost' => 6,
                         'status.stored.in.container' => 7,
                         'status.pulled.out.of.container' => 8,
                         'status.added.to.case' => 9,
                         'status.removed.from.case' => 10,
                         'status.unbind.reservation' => 11,
                         'status.used' => 12,
                         'status.edited' => 13,
                         'status.saved.image' => 14);
    
    static $vstatusToId = array ("status.neutralize" => helper::VSTATUS_NEUTRALISIERT);
    
    
    
    
    
}
