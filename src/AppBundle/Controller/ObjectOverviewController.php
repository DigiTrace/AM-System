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
   

namespace AppBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use AppBundle\Entity\Objekt;


/**
 * Description of ObjectOverviewController
 *
 * @author root
 */
class ObjectOverviewController extends Controller{
   
    
    /**
     * @Route("/objekte/faq", name="search_objects_faq")
     */
    public function search_faqAction(Request $request)
    {        
        return $this->render('default/search_objects_faq.twig');
    }
    
    
    /**
     * @Route("/objekte", name="search_objects")
     */
    public function search_objects(Request $request)
    {     
        /*
         * In dieser Funktion werden alle Objekte dargestellt,
         * wobei diese Filterbar sind, sprich Beispielsweise per
         * Suchbegriff gefunden werden kann.
         * Wenn ein Objekt gefunden worden ist, kann die Detailansicht
         * des Objekts aufgerufen werden.
         * Auch soll es möglich sein, von hier aus neue Objekte einzutragen.
         */
        
        $searchform = $this->get_SearchForm();
        
        
        // Mit diesen Befehl wird festgestellt, ob eine Aenderung durchgefuehrt
        // worden ist
        $searchform->handleRequest($request);
       
        /* Wenn ein Objekt eingetragen wird, wird erst nach Validierung das Objekt
         * in der Datenbank festgeschrieben
         */
        $searchword = null;
        $objekte = null;
        
        if ($this->get('session')->get('anzahleintraege') == null){
            $this->get('session')->set('anzahleintraege',25);
        }
        
        
        if ($searchform->isSubmitted() && $searchform->isValid()) {
             
            $searchword = $searchform->getData()['suchwort'];
            $anzahleintraege = $searchform->getData()['anzahleintraege'];
            if($anzahleintraege != null){
                $this->get('session')->set('anzahleintraege', $anzahleintraege);
            }
        }
        
        
        if($searchword == null){
             $searchword =  $request->get("suche");
        }
        
        
        if($searchword == null){
            
            $em    = $this->get('doctrine.orm.entity_manager');
            $dql   = "SELECT o FROM AppBundle:Objekt o";
            $query = $em->createQuery($dql);
            
        }
        else{
            $query = $this->create_search_query($searchword);
        } 
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $this->get('session')->get('anzahleintraege')/*limit per page*/,
            array(
                'defaultSortFieldName' => 'o.Barcode_id',
                'defaultSortDirection' => 'asc',
            )
        );
        
        
        return $this->render('default/search_objects.html.twig', array(
            'searchform'=> $searchform->createView(),
            'objekte'=> $objekte,
            'pagination' => $pagination
        ));
        
    }
    
    
    private function create_search_query($searchword){
         // TODO fuer weitere Daten, zeitstempel
        $em = $this->getDoctrine()
                ->getManager();
        $string = 'SELECT o '
                . 'FROM AppBundle:Objekt o '
                . 'LEFT JOIN AppBundle:Historie_Objekt ho '
                . 'WITH o.Barcode_id = ho.Barcode_id '
                . 'LEFT JOIN AppBundle:Datentraeger d '
                . 'WITH o.Barcode_id = d.Barcode_id '
                . 'JOIN AppBundle:Nutzer n '
                . 'WITH n.id = o.Nutzer_id '
                . 'LEFT JOIN AppBundle:Nutzer r '
                . 'WITH o.Reserviert_von = r.id '
                . 'LEFT JOIN AppBundle:Nutzer rh '
                . 'WITH ho.Reserviert_von = rh.id '
                . 'LEFT JOIN AppBundle:Nutzer hn '
                . 'WITH hn.id = ho.Nutzer_id '
                . 'LEFT JOIN AppBundle:Objekt so '
                . 'WITH so.Barcode_id = o.Standort '
                . 'LEFT JOIN AppBundle:Fall c '
                . 'WITH c.id = o.Fall_id '
                . 'LEFT JOIN AppBundle:Fall hc '
                . 'WITH hc.id = ho.Fall_id '
                . "WHERE ";

        $parameters = array();

        //WORKING PATTERN; NICHT EDITIEREN, fehlt <> sache
        //$pattern = '/(?J)((?<kriterium>\w+):("|\')(?<value>[\w| ]+)("|\')|'
        //        . '(?<kriterium>\w+):(?<value>\w+))/';

        // Ueberprueft, ob gilt: kriterium:"wert wert" oder 
        //                       kriterium:'wert wert' oder 
        //                       kriterium:wert ist
        // <> mit drin in Value 
        $pattern = '/(?J)((?<kriterium>\w+):("|\')(?<value>[\w| <>\-\.!üÜöÖäÄ]+)("|\')|'
                . '(?<kriterium>\w+):(?<value>[\w<>\-\.!üÜöÖäÄ]+))/';
        $generalvaluepattern = '/(?<operator>[!]?)(?<value>[\w üÜöÖäÄ]+)/';
        $countmatches =  preg_match_all($pattern,
                            $searchword,
                            $matches,
                            PREG_SET_ORDER);
        
        $simplewhereconditions= array("name" => array("o.Name like :name AND","o.Name not like :name AND"),
                                "mdesc" => array("o.Verwendung like :mdesc AND","o.Verwendung not like :mdesc AND"),
                                "hdesc" => array("ho.Verwendung like :hdesc AND","ho.Verwendung not like :hdesc AND"),
                                "mu" => array("n.username like :mu AND","n.username NOT like :mu AND"),
                                "hu" => array("hn.username like :hu AND","hn.username NOT like :hu AND"),
                                "storedin" => array("so.Name like :storedin AND","so.Name not like :storedin AND"),
                                "mcase" => array("c.case_id like :mcase AND","c.case_id not like :mcase AND"),
                                "hcase" => array("hc.case_id like :hcase AND","hc.case_id not like :hcase AND"),
                                "type" => array("d.Bauart like :type AND","d.Bauart not like :type AND"),
                                "ff" => array("d.Formfaktor like :ff AND","d.Formfaktor not like :ff AND"),
                                "prod" => array("d.Hersteller like :prod AND","d.Hersteller not like :prod AND"),
                                "modell" => array("d.Modell like :modell AND","d.Modell not like :modell AND"),
                                "pn" => array("d.PN like :pn AND","d.PN not like :pn AND"),
                                "sn" => array("d.SN like :sn AND","d.SN not like :sn AND"),
                                "connection" => array("d.Anschluss like :anschluss","d.Anschluss not like :anschluss"));


        $translator = $this->get('translator');
        $errors = "";
        foreach($matches as $match){
            $temp = "";
            switch($match["kriterium"]):
                case "name":
                case "mdesc":
                case "hdesc":
                case "mu":
                case "hu":
                case "storedin":
                case "mcase":
                case "hcase":
                case "type":
                case "prod":
                case "modell":
                case "pn":
                case "sn":
                case "connection":
                    $success = preg_match($generalvaluepattern,
                                    $match['value'],
                                    $generalmatch);
                    if($success == true){
                        
                        $generalmatch['operator'] == "!"? $i = 1 : $i = 0;
                        
                        $string = $string . " ".$simplewhereconditions[$match['kriterium']][$i]." ";
                        $parameters[$match['kriterium']] = "%".$generalmatch['value']."%";
                    }
                    
                    break;

                case "c":
                    
                    $success = preg_match($generalvaluepattern,
                                    $match['value'],
                                    $categoriematch);
                    
                    
                    $searchedcategories = array();
                    
                    if($success == true){
                        foreach(helper::$kategorienToId as $key => $value){
                            if(stripos($translator->trans($key), $categoriematch['value']) !== false){                                
                                array_push($searchedcategories, $value);
                            }
                        }
                        
                        if(count($searchedcategories) != 0){
                            if($categoriematch['operator'] == "!"){
                                $string = $string . " o.Kategorie_id not in (:categorie) AND ";                                
                            }
                            else{
                                $string = $string . " o.Kategorie_id in (:categorie) AND ";
                            }
                            
                            $parameters["categorie"] = $searchedcategories; 
                        }else{
                            // if Non of the category was found, fault event
                            $string = $string . " 1 = 0 AND ";
                        }
                    }
                    break;

                case "s":
                    $success = preg_match($generalvaluepattern,
                                    $match['value'],
                                    $statusmatch);
                    
                    $searchedstatus = array();
                    
                    if($success == true){
                        foreach(helper::$statusToId as $key => $value){
                            if(stripos($translator->trans($key), $statusmatch['value']) !== false){
                                array_push($searchedstatus, $value);
                            }
                        }
                        if(count($searchedstatus) != 0){
                            if($statusmatch['operator'] == "!"){
                                $string = $string . " o.Status_id not in (:status) AND ";
                            }
                            else{
                                $string = $string . " o.Status_id in (:status) AND ";
                            }
                            $parameters["status"] = $searchedstatus;
                        }
                        else{
                            // if Non of the status was found, fault event
                            $string = $string . " 1 = 0 AND ";
                        }
                    }
                    break;

                case "mr":
                    
                    switch($match['value']){
                        case "true":
                            $string = $string . " o.Reserviert_von is not null AND ";
                            break;
                        case "false":
                            $string = $string . " o.Reserviert_von is null AND ";
                            break;
                        default;
                            $string = $string . " r.username = :reserved AND ";
                            $parameters["reserved"] = $match['value'];
                            break;
                    }
                    break;

                case "hr":
                    
                    switch($match['value']){
                        case "true":
                            $string = $string . " ho.Reserviert_von is not null AND ";
                            break;
                        case "false":
                            $string = $string . " ho.Reserviert_von is null AND ";
                            break;
                        default;
                            $string = $string . " rh.username = :histreserved AND ";
                            $parameters["histreserved"] = $match['value'];
                            break;
                    }
                    
                    break;

                case "size":
                    $success = preg_match('/(?<operator>[<>]?)(?<size>[\d]+)/',
                                    $match['value'],
                                    $sizematch);
                    
                    if($success == true){
                        if($sizematch['operator'] != ""){
                            $string = $string . " d.Groesse ".$sizematch['operator']." :size AND ";
                            $parameters["size"] = $sizematch['size'];
                        }
                        else{
                            $string = $string . " d.Groesse = :size AND ";
                            $parameters["size"] = $match['value'];
                        }
                        
                    }
                    else{
                        $this->addFlash("danger",'size.value.not.valid');
                    }
                    
                    
                    break;
                    
                case "mdate":
                    $success = preg_match('/(?<operator>[<>]?)(?<day>[\d]{2})[-\.]{1}(?<month>[\d]{2})[-\.]{1}(?<year>[\d]{4})/',
                                    $match['value'],
                                    $mdatematch);
                    
                    if($success == true){
                        if(checkdate($mdatematch['month'], $mdatematch['day'], $mdatematch['year']) == true){
                            if($mdatematch['operator'] != ""){
                                $string = $string . " DATE_DIFF(o.Zeitstempel,:zeitstempel) ".$mdatematch['operator']." 0 AND ";
                            }
                            else{
                                $string = $string . " DATE_DIFF(o.Zeitstempel,:zeitstempel) = 0 AND ";
                            }
                            $parameters["zeitstempel"] = new \DateTime($mdatematch['day']."-".$mdatematch['month']."-".$mdatematch['year']);
                        }
                        else{
                            $this->addFlash("danger",'timestamp.value.not.valid');
                        }
                    }
                    else{
                        $this->addFlash("danger",'timestamp.value.not.valid');
                    }
                    
                    
                    break;
                    

                default:
                    $errors = $errors ." ". $match['kriterium'];
                    break;
            endswitch;   
        }
        $string = $string . "1=1";

        if($errors != ""){
            $this->addFlash("danger",
                            $translator->trans('criteria.was.not.found %criteria%',array("%criteria%" => $errors))
                    );
        }

        if($countmatches > 0){
            $query = $em->createQuery($string);

            foreach ($parameters as $d => $value){
                $query->setParameter($d,$value);
            }      
        }
        else{
            $query = $em->createQuery('SELECT o '
                    . 'FROM AppBundle:Objekt o '
                    . 'LEFT JOIN AppBundle:Historie_Objekt ho '
                    . 'WITH o.Barcode_id = ho.Barcode_id '
                    . 'LEFT JOIN AppBundle:Datentraeger d '
                    . 'WITH o.Barcode_id = d.Barcode_id '
                    . "WHERE o.Name like :searchword "
                    . "OR o.Verwendung like :searchword "
                    . "OR o.Barcode_id like :searchword ")
                    ->setParameter(":searchword" , "%".$searchword."%");  
        }
        return $query;
    }
    
    
     
    
    /**
     * @Route("/objekt-scanner", name="search_objects_scanner")
     */
    public function search_objects_scanner(Request $request)
    {
        /*
         * Im Grunde eine Art "API" zum Verwenden des Barcode Scanners
         * Durch das Scannen des jeweiligen Barcodes soll automatisch zur
         * Detailansicht des jeweiligen Objektes geführt wird.
         */
        
       $searchidform = $this->createFormBuilder()
                ->add("suchwort", TextType::class,
                        array('required' => false,
                              'label' => 'scan_object_with_scanner',
                              'attr' => array('autofocus' => true)))
               ->getForm();
        
       
       
       $searchidform->handleRequest($request);
       $searchword = null;
        
        if ($searchidform->isSubmitted() && $searchidform->isValid()) {
            $searchword = $searchidform->getData()['suchwort']; 
        }
        
        if($searchword != null){
            $em = $this->getDoctrine()->getManager();
            $object = $em->getRepository('AppBundle:Objekt')->find($searchword);
            
            if($object){
                return $this->redirectToRoute("detail_object", array('id' => $object->getBarcode()));
            }
            else{
                $this->addFlash("danger", "object_was_not_found");
            }
        }
        
       
        
        return $this->render('default/search_objects_scanner.html.twig', [
            'suche' => $searchidform->createView(),
        ]);
    }
    
    
   
    
    private function get_SearchForm(){
        
             
        $searchform = $this->createFormBuilder(null,
                        array('method'=>'GET', 
                              'csrf_protection' => false, 
                              'attr' => array('class' => 'navbar-form navbar-right')))
                ->add("suchwort", SearchType::class,array(
                        'required' => false,
                        'label'=> false,
                        'attr' => array('size'=> 30)));
        
        $searchform->add("anzahleintraege", ChoiceType::class, array(
                    'choices' => array(
                        '25' => '25',
                        '50' => '50',
                        '100' => '100',
                        '1000' => '1000'),
                    'label'=> false,'data'=> $this->get('session')->get('anzahleintraege'),'attr'=>array('onchange' => 'submit();')));
        
        $searchform->add("suchen", SubmitType::class,array("attr"=>array("style" => "display: none")));
        
        return $searchform->getForm();
        
        
    }
    
    
    
    

}
