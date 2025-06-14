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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;



#neu
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\Objekt;
use Symfony\Component\HttpFoundation\RequestStack;
#use Symfony\Component\Form\FormBuilderInterface;



/**
 * Description of ObjectOverviewController
 *
 * @author root
 */
class ObjectOverviewController extends AbstractController{
   
    
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
    public function search_objects(RequestStack $requestStack,
                                    Request $request, 
                                    ManagerRegistry $doctrine,
                                    PaginatorInterface $paginator,
                                    TranslatorInterface $translator)
    {     
        /*
         * In dieser Funktion werden alle Objekte dargestellt,
         * wobei diese Filterbar sind, sprich Beispielsweise per
         * Suchbegriff gefunden werden kann.
         * Wenn ein Objekt gefunden worden ist, kann die Detailansicht
         * des Objekts aufgerufen werden.
         * Auch soll es möglich sein, von hier aus neue Objekte einzutragen.
         */
        
        $searchform = $this->get_SearchForm($requestStack);
        $session=$requestStack->getSession();
        
        // Mit diesen Befehl wird festgestellt, ob eine Aenderung durchgefuehrt
        // worden ist
        $searchform->handleRequest($request);
       
        /* Wenn ein Objekt eingetragen wird, wird erst nach Validierung das Objekt
         * in der Datenbank festgeschrieben
         */
        $searchword = null;
        $objekte = null;
        
        if ($session->get('anzahleintraege') == null){
            $session->set('anzahleintraege',25);
        }
        
        
        if ($searchform->isSubmitted() && $searchform->isValid()) {
             
            $searchword = $searchform->getData()['suchwort'];
            $anzahleintraege = $searchform->getData()['anzahleintraege'];
            if($anzahleintraege != null){
                $session->set('anzahleintraege', $anzahleintraege);
            }
        }
        
        
        if($searchword == null){
             $searchword =  $request->get("suche");
        }
        
        
        if($searchword == null){
            
            $em    = $doctrine->getManager();
            $dql   = "SELECT o FROM App:Objekt o";
            $query = $em->createQuery($dql);
            
        }
        else{
            $query = $this->create_search_query($doctrine,$searchword, $translator);
        } 
        
        
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $session->get('anzahleintraege')/*limit per page*/,
            array(
                'defaultSortFieldName' => 'o.barcode_id',
                'defaultSortDirection' => 'asc',
            )
        );
        
        
        return $this->render('default/search_objects.html.twig', array(
            'searchform'=> $searchform->createView(),
            'objekte'=> $objekte,
            'pagination' => $pagination
        ));
        
    }
    
    
    private function create_search_query(ManagerRegistry $doctrine,$searchword, $translator){
        
      $repository = $doctrine->getRepository(Objekt::class); 
      
      $query = $repository->createQueryBuilder('o');
      $query->leftjoin("App:HistorieObjekt", "ho","WITH","o.barcode_id = ho.barcode_id");
      $query->leftjoin("App:Datentraeger"   , "d" ,"WITH","o.barcode_id =  d.barcode_id");
      $query->leftjoin("App:Nutzer"         , "n" ,"WITH","o.nutzer_id =  n.id");
      $query->leftjoin("App:Nutzer"         , "hn" ,"WITH","ho.nutzer_id =  hn.id");
      $query->leftjoin("App:Nutzer"         , "r" ,"WITH","o.reserviert_von = r.id");
      $query->leftjoin("App:Nutzer"         , "rh","WITH","ho.reserviert_von = rh.id");
      $query->leftjoin("App:Objekt "        , "so","WITH","so.barcode_id = o.standort");
      $query->leftjoin("App:Objekt "        , "hso","WITH","hso.barcode_id = ho.standort");
      $query->leftjoin("App:Fall"           , "c","WITH" ,"c.id = o.fall_id");
      $query->leftjoin("App:Fall"           , "hc","WITH","hc.id = ho.fall_id ");
      
      

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
        $numbervaluepattern = '/(?<operator>[!]?)(?<value>[\d]+)/';
        $countmatches =  preg_match_all($pattern,
                            $searchword,
                            $matches,
                            PREG_SET_ORDER);
                
        
        $simplewhereconditions= array("name" => "o.name",
                                "mdesc" => "o.verwendung",
                                "barcode" => "o.barcode_id",
                                "notice" => "o.notiz",
                                "hdesc" => "ho.verwendung",
                                "mu" => "n.fullname",
                                "hu" => "hn.fullname",
                                "mstoredin" => "so.barcode_id",
                                "hstoredin" => "hso.barcode_id",
                                "mcase" => "c.case_id",
                                "hcase" => "hc.case_id",
                                "type" => "d.bauart",
                                "ff" => "d.formfaktor",
                                "prod" => "d.hersteller",
                                "modell" => "d.modell",
                                "pn" => "d.pn",
                                "sn" => "d.sn",
                                "connection" => "d.anschluss",
                                "mr" => "r.fullname",
                                "hr" => "hr.fullname");


        
        $errors = "";
        $usedSearchParameters = array();
        $indexparameter = -1;
        foreach($matches as $match){
            $temp = "";
            $indexparameter++;
            $skipparameter = null;
            $success = preg_match($generalvaluepattern,
                                    $match['value'],
                                    $generalmatch);
            if($success == true){
                
                foreach ($simplewhereconditions as $key =>$value){
                    
                    if($match['kriterium'] == $key){
                        
                        switch($generalmatch['value']):
                        case "true":
                        case "True":
                            $query->andwhere($simplewhereconditions[$match['kriterium']]." is not null ");
                            $skipparameter = true;
                            break;
                        case "false":
                        case "False":
                            $query->andwhere($simplewhereconditions[$match['kriterium']]." is null ");
                            $skipparameter = false;
                            break;
                        endswitch;
                    }
                } 
                   
                    
                        
                if($skipparameter !== null){
                    array_push($usedSearchParameters,
                               array($match['kriterium'],
                                   ($skipparameter)? "true":"false")
                              );
                    continue;
                }
            }
                    
            
            
            switch($match["kriterium"]):
                case "name":
                case "mdesc":
                case "barcode":
                case "notice":
                case "hdesc":
                case "mu":
                case "hu":
                case "mstoredin":
                case "hstoredin":
                case "mcase":
                case "hcase":
                case "type":
                case "prod":
                case "modell":
                case "pn":
                case "sn":
                case "connection":
                case "hr":
                case "mr":
                    $success = preg_match($generalvaluepattern,
                                    $match['value'],
                                    $generalmatch);
                    if($success == true){
                        if($generalmatch['operator'] != "!")
                            $query->andwhere($simplewhereconditions[$match['kriterium']]." like :parameter".$indexparameter);
                        else
                            $query->andwhere($simplewhereconditions[$match['kriterium']]." not like :parameter".$indexparameter);

                        $parameters["parameter".$indexparameter] = "%".$generalmatch['value']."%";
                        
                        array_push($usedSearchParameters,
                                   array($match['kriterium'],
                                       $generalmatch['operator'].$generalmatch['value'])
                                  );
                    }

                    break;

                case "c":
                    
                    $success = preg_match($numbervaluepattern,
                                    $match['value'],
                                    $categoriematch);
                    
                    if($success == true){
                        
                        if($generalmatch['value'] >= 0 && 
                           $generalmatch['value'] < Objekt::getCountCategories()){
                            
                            if($generalmatch['operator'] != "!")
                                    $query->andwhere("o.kategorie_id = :parameter".$indexparameter);
                                else
                                    $query->andwhere("o.kategorie_id != :parameter".$indexparameter);
                               
                            $parameters["parameter".$indexparameter] = $generalmatch['value'];
                            array_push($usedSearchParameters,
                                   array($match['kriterium'],
                                       $generalmatch['operator'].$translator->trans(Objekt::getKategorieNameFromId($generalmatch['value'])))
                                  );
                        }
                        else{
                            $this->addFlash("danger",'category.number.is.wrong');
                        }  
                    }
                    else{
                        $this->addFlash("danger",'category.number.is.wrong');
                    } 
                    break;
                
                case "s":
                    $success = preg_match($numbervaluepattern,
                                    $match['value'],
                                    $statusmatch);
                    
                    
                    if($success == true){
                        
                        if($generalmatch['value'] >= 0 && 
                           $generalmatch['value'] < Objekt::getCountStatues()){
                            
                            if($generalmatch['operator'] != "!")
                                    $query->andwhere("o.status_id = :parameter".$indexparameter);
                                else
                                    $query->andwhere("o.status_id != :parameter".$indexparameter);
                               
                            $parameters["parameter".$indexparameter] = $generalmatch['value'];
                            array_push($usedSearchParameters,
                                   array($match['kriterium'],
                                       $generalmatch['operator'].$translator->trans(Objekt::getStatusNameFromId($generalmatch['value'])))
                                  );
                        }
                        else{
                            $this->addFlash("danger",'status.number.is.wrong');
                        }  
                    }
                    else{
                        $this->addFlash("danger",'status.number.is.wrong');
                    } 
                    break;
            
                case "size":
                    $success = preg_match('/(?<operator>[!<>]?)(?<size>[\d]+)/',
                                    $match['value'],
                                    $sizematch);
                    
                    if($success == true){
                        
                        switch ($sizematch['operator']):
                            case "<":
                            case ">":
                                $query->andwhere("d.groesse ".$sizematch['operator']." :parameter".$indexparameter);
                                break;
                            case "!":
                                $query->andwhere("d.groesse != :parameter".$indexparameter);
                                break;
                            case "":
                                $query->andwhere("d.groesse = :parameter".$indexparameter);
                                break;
                        endswitch;
                        $parameters["parameter".$indexparameter] = $generalmatch['value'];
                        array_push($usedSearchParameters,
                                   array($match['kriterium'],
                                       $generalmatch['operator'].$generalmatch['value'])
                                  );
                    }
                    else{
                        $this->addFlash("danger",'size.value.not.valid');
                    }
                    
                    
                    break;
                    
                case "mdate":
                    $success = preg_match('/(?<operator>[!<>]?)(?<day>[\d]{2})[-\.]{1}(?<month>[\d]{2})[-\.]{1}(?<year>[\d]{4})/',
                                    $match['value'],
                                    $mdatematch);
                    
                    if($success == true){

                        if(checkdate($mdatematch['month'], $mdatematch['day'], $mdatematch['year']) == true){
                            
                            switch ($mdatematch['operator']):
                            case "<":
                            case ">":
                                $query->andwhere("DATE_DIFF(o.zeitstempel,:parameter".$indexparameter.") ".$mdatematch['operator']." 0 ");
                                break;
                            case "!":
                                $query->andwhere("DATE_DIFF(o.zeitstempel,:parameter".$indexparameter.") != 0 ");
                                break;
                            case "":
                                $query->andwhere("DATE_DIFF(o.zeitstempel,:parameter".$indexparameter.") = 0 ");
                                break;
                            endswitch;
                            $parameters["parameter".$indexparameter] = new \DateTime($mdatematch['day']."-".$mdatematch['month']."-".$mdatematch['year']);
                            
                            array_push($usedSearchParameters,
                                   array($match['kriterium'],
                                       $generalmatch['operator'].$match['value'])
                                  );
                        }
                        else{
                            $this->addFlash("danger",'timestamp.value.not.valid');
                        }
                    }
                    else{
                        $this->addFlash("danger",'timestamp.value.not.valid');
                    }
                    
                    break;
                    
                case "caseactive":
                    switch ($match['value']):
                        case "True":
                        case "true":
                            $query->andwhere("c.istAktiv = true");
                            break;
                        case "false":
                        case "False":
                            $query->andwhere("c.istAktiv = false");
                            break;
                        default :
                            $this->addFlash("danger",'only.boolean.values.allowed');
                            break;
                    endswitch;
               
                   
                    array_push($usedSearchParameters,
                               array($match['kriterium'],
                                   $match['value'])
                              );

                    break;
                    

                default:
                    $errors = $errors ." ". $match['kriterium'];
                    break;
            endswitch;   
        }
        
        
     

        if($errors != ""){
            $this->addFlash("danger",
                            $translator->trans('criteria.was.not.found %criteria%',array("%criteria%" => $errors))
                    );
        }

        if($countmatches > 0){

            foreach ($parameters as $d => $value){
                $query->setParameter($d,$value);
            } 
            $string_used_parameters = "";
            foreach ($usedSearchParameters as $value){
                $string_used_parameters = $string_used_parameters." ". $value[0].":".$value[1];
            } 
            $this->addFlash("success",$string_used_parameters);
            
        }
        else{
            $em =$doctrine->getManager();
            $query = $em->createQuery('SELECT o '
                    . 'FROM App:Objekt o '
                    . 'LEFT JOIN App:HistorieObjekt ho '
                    . 'WITH o.barcode_id = ho.barcode_id '
                    . 'LEFT JOIN App:Datentraeger d '
                    . 'WITH o.barcode_id = d.barcode_id '
                    . "WHERE o.name like :searchword "
                    . "OR o.verwendung like :searchword "
		    . "OR o.notiz like :searchword "
                    . "OR o.barcode_id like :searchword "
                    . "OR d.sn like :searchword ")
                    ->setParameter(":searchword" , "%".$searchword."%");  
        }
        return $query;
    }
    
    
     
    
    /**
     * @Route("/objekt-scanner", name="search_objects_scanner")
     */
    public function search_objects_scanner(ManagerRegistry $doctrine,Request $request)
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
            $em = $doctrine->getManager();
            $object = $em->getRepository(Objekt::class)->find($searchword);
            
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
    
    
   
    
    private function get_SearchForm(RequestStack $requestStack){
        
        $session = $requestStack->getSession();
        $searchform = $this->createFormBuilder(null,
                        array('method'=>'GET', 
                              'csrf_protection' => false, 
                              'attr' => array('class' => 'navbar-form navbar-right')))
                ->add("suchwort", SearchType::class,array(
                        'required' => false,
                        'label'=> false,
                        'attr' => array('size'=> 30)))
                ->setAction($this->generateURL('search_objects'));
        
        $searchform->add("anzahleintraege", ChoiceType::class, array(
                    'choices' => array(
                        '25' => '25',
                        '50' => '50',
                        '100' => '100',
                        '1000' => '1000'),
                    'label'=> false,'data'=> $session->get('anzahleintraege'),'attr'=>array('onchange' => 'submit();')));
        
        $searchform->add("suchen", SubmitType::class,array("attr"=>array("style" => "display: none")));
        
        return $searchform->getForm();
        
        
    }
    
    
    
    

}
