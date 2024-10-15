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

//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SearchTypeType;
use App\Form\Type\ActionChooseType;
use App\Form\Type\AddObjectType;
use Symfony\Component\HttpFoundation\File\File;
use App\Entity\Objekt;
use App\Entity\Fall;
use App\Entity\Datentraeger;
use App\Entity\Nutzer;
use App\Controller\helper;
# zu entfernen
#use Symfony\Component\HttpFoundation\Session\SessionInterface;



# neu 

use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;



class ObjectDetailController extends AbstractController{
    
    
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }









    /*
     * Diese Funktion ueberprueft, ob der uebermittelte Barcode den konventionen
     * entsprechen, das heißt neunstellig mit vordefinierten woertern.
     */
    private function checkBarcode($barcode,$kategorie){
        
        $prafix = str_split($barcode,4)[0];
        
        if( ($prafix == "DTAS" && ($kategorie == Objekt::KATEGORIE_ASSERVAT || $kategorie == Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER)||
             $prafix == "DTHD" && $kategorie == Objekt::KATEGORIE_DATENTRAEGER||
             $prafix == "DTHW" && ($kategorie == Objekt::KATEGORIE_AUSRUESTUNG || $kategorie == Objekt::KATEGORIE_BEHAELTER) || 
             $prafix == "DTAK" && $kategorie == Objekt::KATEGORIE_AKTE) 
            && strlen($barcode) == 9){
            return $prafix;
        }
        return false;
    }    
    
    
    
    /**
     * @Route("/objekt/anlegen", name="add_object")
     */
    public function add_Object(Request $request,ManagerRegistry $doctrine){
        
        $usr= $this->getUser();
          
        $new_object = new Objekt();
        
     /* 
      @Assert\Regex(
          pattern="/DT(AS|HW|AK|HD)\\d{5}$/i",
          htmlPattern="/DT(AS|HW|AK|HD)\\d{5}$/i",
      )*/
        
        
        $addform = $this->createForm(AddObjectType::class, 
                                       null,
                                       ['entityManager' => $doctrine->getManager()]);

        $addform->handleRequest($request);

        
        if (    $addform->isSubmitted() && 
                $addform->isValid() ) {
            
            $info = $addform->getData();
            if($this->checkBarcode($info['barcode_id'],$info['kategorie_id'])){
                $em = $doctrine->getManager();
                
                if(($em->getRepository(Objekt::class)->find($addform->getData()['barcode_id'])) == null){
                   $new_object->setStatus(Objekt::STATUS_EINGETRAGEN);
                   $new_object->setBarcode($info['barcode_id']);
                   $new_object->setName($info['name']);
                   $new_object->setVerwendung($info['verwendung']);
                   $new_object->setNotiz($info['notiz']);
                   $new_object->setKategorie($info['kategorie_id']);
                   $new_object->setZeitstempelumsetzung($info['dueDate']);

                   
                   $new_object->setNutzer($em->getRepository(Nutzer::class)->findOneBy(array('id' => $usr->getId())));

                   $em->persist($new_object);
                   
                   
                   
                   if($info['case'] != ""){
                        $this->add_to_case($doctrine,$new_object->getBarcodeId(),$info['case'], $info['dueDate'],"Aufgrund der Eintragung automatisiert hinzugefügt");
                   }
                   
                   
                   
                   
                   if($info['kategorie_id'] == Objekt::KATEGORIE_DATENTRAEGER ||
                      $info['kategorie_id'] == Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER){
                       
                       $new_datentraeger = new Datentraeger($info);
                       
                       $em->persist($new_datentraeger);
                   }
                   else{
                       if($info['bauart'] != null     ||
                          $info['formfaktor'] != null ||
                          $info['groesse'] != null    ||
                          $info['hersteller'] != null ||
                          $info['modell'] != null     ||
                          $info['sn'] != null         ||
                          $info['pn'] != null         ||
                          $info['anschluss'] != null){
                           
                            
                            $this->addFlash('danger','not.valid.object.infos');
                            
                            return $this->render('default/add_object_form.html.twig', array(
                            'addform' => $addform->createView(),
                            )); 
                       }
                   }
                   
                   $em->flush();

                   if($addform->get("save")->isClicked() == true){
                       return $this->redirectToRoute('detail_object',array('id' =>$new_object->getBarcode()) );  
                   }
                   elseif($addform->get("saveandaddsimilar")->isClicked() == true){
                       $this->addFlash('success',$this->translator->trans('object.successfully.saved %object%',array("%object%" => $new_object->getBarcode())));
                        
                   }
                        
                   else{
                       $this->addFlash('success',$this->translator->trans('object.successfully.saved %object%',array("%object%" => $new_object->getBarcode())));
                       $addform = $this->createForm(AddObjectType::class, 
                                       null,
                                       ['entityManager' => $doctrine->getManager()]);   
                   }
                   
                }
                else
                {
                    // Wenn der Barcode in der Datenbank bereits verfuegbar ist
                    $this->addFlash('danger','barcode.already.exists.in.database');
                }
            }
            else
            {
                $this->addFlash('danger','barcode.not.valid');
            }
        }
        if(  $addform->isSubmitted() && $addform->isValid() == false){
            $this->addFlash('danger','object.is.not.saved');
        }
        
        return $this->render('default/add_object_form.html.twig', array(
            'addform' => $addform->createView(),
        )); 
      } 
    
       
    
    
    
    /**
     * @Route("/objekte/aendern/", name="select_new_status_for_multiple_objects")
     */
    public function select_new_status_for_multiple_objects(Request $request,RequestStack $requeststack, ManagerRegistry $doctrine)
    {     
       
        $chooseform = $this->createForm(ActionChooseType::class, 
                                        null,
                                        ['entityManager' => $doctrine->getManager()]);
        
        $session=$requeststack->getSession();

        $chooseform->handleRequest($request);
        if (    $chooseform->isSubmitted() && 
                $chooseform->isValid() ) {
            
            $temp = $chooseform->getData();
            
            // if a Objects has to be stored or added to case, this action cant
            // proceed, if contextthing isnt set
            if(($temp["newstatus"] == Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT ||
                $temp["newstatus"] == Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT) &&
                $temp["contextthings"] == null){
                
                $this->addFlash('danger','selected_action_needs_contextthings');
            }
            else{   
                $session->set("newstatus",$temp["newstatus"]);
                $session->set("newdescription",$temp["newdescription"]);
                $session->set("contextthings",$temp["contextthings"]);
                $session->set("dueDate",$temp["dueDate"]);
                
                return $this->redirectToRoute('alter_multiple_objects');
            }
            
        }
        else{
            $this->addFlash("info",'action_description_mass_update_part1');
        }
        return $this->render('default/select_action.html.twig', array(
            'chooseform'=> $chooseform->createView(),
        ));
        
    }
    
    
    
    
      
      
    /**
     * @Route("/objekte/aendern/in", name="alter_multiple_objects")
     */
    public function alter_multiple_objects(Request $request,RequestStack $requeststack,ManagerRegistry $doctrine)
    {     
        $session=$requeststack->getSession();
        $errorIds = "";
        $errorActionOnObject = "";
        $builder = $this->createFormBuilder(); 
        $builder->add('objects', CollectionType::class, array(
            'entry_type' => TextType::class,
            'allow_add' => true,
            'prototype' => true,
            'entry_options'=> array('label' => false,
                                    'attr' => array('onkeyup' => 'checklength(this)',
                                                    'maxlength' => '9',
                                                    )),
                                    'required'=> false,
                                    'label' => 'objects'
            ));
        
         
        $chooseform = $builder->add('continue',SubmitType::class,array('label' => 'label.do.action'))
                    ->getForm();
        
       
        $chooseform->handleRequest($request);
        
        
        
        if (    $chooseform->isSubmitted() && 
                $chooseform->isValid() &&
                $session->get("newstatus") != null &&
                $session->get("newdescription") != null && 
                $session->get("dueDate") != null ) {
            $store_object = null;
            $case = null;
            
            $date = $session->get("dueDate");
            $newstatus = $session->get("newstatus");
            
            $ids = $chooseform->getData()['objects'];
            
            //Status has to be evaluted, cause relationship has to be validated 
            if($newstatus == Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT){
                $store_object = $doctrine->getRepository(Objekt::class)->find($session->get("contextthings"));
            }
            if($newstatus == Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT){
                $case = $doctrine->getRepository(Fall::class)->find($session->get("contextthings"));  
            }
            
            $objects = [];
            foreach( $ids as $id){
                $reason = "";
                $contextreason =  "";
                
                if($id != ""){
                    $object = $doctrine->getRepository(Objekt::class)->find($id);

                    if($object == null){
                        $errorIds = $errorIds . $id."\r\n";
                        continue;
                    }
                    // Check if Input has duplicate Objects
                    if($objects == null){
                        array_push($objects, $object); 
                        $toadd = true;
                    }
                    else{
                        $toadd = true;
                        foreach ($objects as $key => $tempobject){

                            if($object->getBarcode() == 
                                   $tempobject->getBarcode()){
                                $toadd = false;
                            }


                            // special case for container, they will be updated 
                            // via the change of the container
                            /*if($tempobject->getKategorie() == helper::KATEGORIE_BEHAELTER){
                                if($this->has_object_relationship_with_store_object($object, $tempobject) == true){
                                    $toadd = false;
                                } 
                            }*/
                            // reverse case
                            /*if($object->getKategorie() == helper::KATEGORIE_BEHAELTER){
                                unset($objects[$key]);
                            }*/
                        }
                        if($toadd  == true){
                            array_push($objects, $object);
                        }
                    }

                    // Check if the Object id valid with the new status
                    if($toadd == true){
                        switch($newstatus){
                            case Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT:
                                $contextthing = $doctrine->getRepository(Objekt::class)->find($session->get("contextthings"));
                                break;
                            case Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT:
                                $contextthing = $doctrine->getRepository(Fall::class)->find($session->get("contextthings"));
                                break;
                            default:
                               $contextthing = null; 
                        }
                        $action_correct = $object->isObjectWithNewStatusValid($newstatus, 
                                                                $contextthing,
                                                                $reason);

                        if($action_correct == false){
                            $message= $this->translator->trans($reason,array("context" => $contextthing));
                            $errorActionOnObject = $errorActionOnObject . $id." : ".$message."\r\n";
                        }
                    }
                }
            }
            
            if($errorActionOnObject != ""){
                $message= $this->translator->trans('objects.action.not.correkt %counts%',array("counts" => $errorActionOnObject));
                $this->addFlash('danger',$message);
                
            }
            if($errorIds != ""){
                $message= $this->translator->trans('objects.not.found %counts%',array("counts" => $errorIds));
                $this->addFlash('danger',$message);
            }
            
            
            if($errorActionOnObject == "" &&
               $errorIds == ""){
                
                
                foreach($objects as $object){
                    
                    switch($newstatus){
                        case Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT:
                            $this->store_object($doctrine,
                                            $object->getBarcode(), 
                                            $store_object->getBarcode(), 
                                            $date, 
                                            $session->get("newdescription"));
                            break;
                        case Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT:
                            $this->add_to_case( $doctrine,
                                           $object->getBarcode(), 
                                           $case->getId(), 
                                            $date, 
                                            $session->get("newdescription"));
                            break;
                        case Objekt::VSTATUS_NEUTRALISIERT:
                            
                            $this->neutralize_object($doctrine,$object,
                                                     $session->get("newdescription"),
                                                     $date);
                            
                            break;
                        default :
                            $this->alter_object($doctrine,
                                            $object, 
                                            $newstatus, 
                                            $session->get("newdescription"), 
                                            $date);
                            break;
                    }
                    
                  
                }
                
                
                 $conn = $doctrine->getConnection();

                // Removed check of new status cause of virtual status
                $sql = '
                    SELECT count(barcode_id) as count FROM ams_Objekt
                    WHERE  UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(zeitstempel) <= 30';
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute();
                
                $count = $result->fetchAssociative()["count"];
                
                // Clear session for securityreasons
                $session->remove("newdescription");
                $session->remove("newstatus");
                $session->remove("contextthings");
                $session->remove("dueDate");
                
                $message= $this->translator->trans('count.objects.are.changed %counts%',array("counts" => $count));
                $this->addFlash('success',$message);
                return $this->redirectToRoute('search_objects');
                
            }
            
        }
        else{
            $this->addFlash("info",$this->translator->trans('action_description_mass_update_part2 %newstatus%',
                array("newstatus" => $this->translator->trans(array_search($session->get("newstatus"),
            Objekt::$statusToId)))));
        }
        
        // When the Site is called directly go to search_objects
        if($session->get("newstatus") == null &&
                $session->get("contextthings") == null &&
                $session->get("newdescription") == null){
                return $this->redirectToRoute('search_objects'); 
        }
        
        return $this->render('default/select_objects_for_selected_action.html.twig', array(
            'chooseform'=> $chooseform->createView(),
            'title' => 'update_chosen_objects',
        ));
        
    }


    /**
     * Show details page of a specific object.
     * 
     * @param Symfony\Component\HttpFoundation\Request $request  Symfony request
     * @param Doctrine\Persistence\ManagerRegistry     $doctrine Database interface
     * @param string                                   $id       DT-ID of object
     */
    #[Route('/objekt/{id}', name: 'detail_object')]
    public function details_object(Request $request, ManagerRegistry $doctrine, $id)
    {
        // query database for object
        $object = $doctrine->getRepository(Objekt::class)->find($id);
        $datentraeger = $doctrine->getRepository(Datentraeger::class)->find($id);

        // check if object was found
        if($object == null){
            $this->addFlash('danger','object_was_not_found');
            
            // redirect to overview page
            return $this->forward('App\Controller\ObjectOverviewController::search_objects', array());
        }
        $em = $doctrine->getManager();

        
        // query object history
        $query = $em->createQuery('SELECT o '
                    . 'FROM App:Historie_Objekt o '
                    . "WHERE o.Barcode_id = :barcode " 
                    . "ORDER by o.historie_id desc ")
                    ->setParameter("barcode",$object->getBarcode());  
        $history_entrys = $query->getResult();
        
        $stored_objects = null;
        // if object is a container, show stored objects
        if($object->getKategorie() == Objekt::KATEGORIE_BEHAELTER){
            
            $query = $em->createQuery('SELECT o '
                    . 'FROM App:Objekt o '
                    . 'LEFT JOIN  App:Fall f WITH o.Fall_id = f.id '
                    . 'WHERE o.Standort = :barcode '
                    . 'ORDER BY f.case_id desc, o.Barcode_id')
                    ->setParameter("barcode",$object->getBarcode());  
            $stored_objects = $query->getResult();
        }
        
        // render object detail view
        return $this->render('default/detail_object.html.twig', [
            'id' => $object->getBarcode(),
            'objekt' => $object ,
            'datentraeger' => $datentraeger,
            'history_entries' => $history_entrys,
            'stored_objects' => $stored_objects,
        ]);
    }
    
    private function check_edited_fields($newVerwendung,$columnname, $oldvalue, $newvalue){
        if(strcmp($oldvalue,
                  $newvalue) != 0){
            $newVerwendung = $newVerwendung. $columnname.": '".$oldvalue.
                     "' => '".$newvalue."'". PHP_EOL;
        }
        return $newVerwendung;
    }
    
    
    
    private function get_EditObjectForm($object,$datentraeger, $filledform){
        
        if($datentraeger != null){
            $field = array(
                "name" => $object->getName(),
                "verwendung" => $object->getVerwendung(),
                "notiz" => $object->getNotiz(),
                "bauart" => $datentraeger->getBauart(),
                "formfaktor" => $datentraeger->getFormfaktor(),
                "groesse" => $datentraeger->getGroesse(),
                "hersteller" => $datentraeger->getHersteller(),
                "modell" => $datentraeger->getModell(),
                "sn" => $datentraeger->getSN(),
                "pn" => $datentraeger->getPN(),
                "anschluss" => $datentraeger->getAnschluss()
            );
        }
        else{
            $field = array(
            "name" => $object->getName(),
            "verwendung" => $object->getVerwendung(),
            "notiz" => $object->getNotiz(),
            );
        }
        
        // Wenn der Nutzer bereits Daten ausgefuellt hat, sollen diese Daten
        // bei eventueller Falscheingabe modifiziert werden koennen
        if($filledform != null){
            $field = $filledform->getData();
            
        }
        $tempform= $this->createFormBuilder(null,array('attr' => array('onsubmit' => "return alertbeforesubmit()")))
            ->add('name',TextType::class, array('attr' => array('value' => $field['name']),
                                                            'constraints' => [new \Symfony\Component\Validator\Constraints\NotBlank()
                                                            ],'label' => 'desc.name'))
            // Bei Textarea Felderm muss der vordefinierte Wert mittels 'data' definiert werden
            ->add('verwendung', TextareaType::class,array('required' => false,'label' => 'desc.usage','data' => $field['verwendung']))
            ->add('notiz', TextareaType::class,array('required' => false,'label' => 'desc.additional.usage','data' => $field['notiz']));
        
        if($datentraeger != null){
        $tempform->add('bauart', TextType::class,array('required' => false,'label' => 'desc.type', 'attr' => array('value' => $field['bauart'])))
                ->add('formfaktor',  TextType::class,array('required' => false, 'label' => 'desc.formfactor','attr' => array('value' => $field['formfaktor'])))
                ->add('groesse', IntegerType::class,array('required' => false,'label' => 'desc.size','attr' => array('value' => $field['groesse'])))

                ->add('hersteller',  TextType::class,array('required' => false,'label' => 'desc.producer' ,'attr' => array('value' => $field['hersteller'])))
                ->add('modell',  TextType::class,array('required' => false,'label' => 'desc.modell','attr' => array('value' => $field['modell'])))
                ->add('sn',  TextType::class,array('required' => false, 'label' => 'desc.sn', 'attr' => array('value' => $field['sn'])))
                ->add('pn',  TextType::class,array('required' => false, 'label' => 'desc.pn', 'attr' => array('value' => $field['pn'])))
                ->add('anschluss',  TextType::class,array('required' => false, 'attr' => array('value' => $field['anschluss'])));
        }
        
        $tempform->add('save',SubmitType::class,array('label' => 'label.do.action'));
            
        return $tempform;
    }


    
    /**
     * @Route("/objekt/{id}/editieren", name="edit_object")
     */
    public function  edit_object(Request $request,ManagerRegistry $doctrine,$id)
    {        
        $em = $doctrine->getManager();
        $object = $doctrine->getRepository(Objekt::class)->find($id);
        $datentraeger = $doctrine->getRepository(Datentraeger::class)->find($id);
        
        if($object == null){
            $this->addFlash('danger','object_was_not_found');
            return $this->redirectToRoute('search_objects'); 
        }
        
        if($object->isObjectWithNewStatusValid(Objekt::STATUS_EDITIERT, 
                                            null, 
                                            $reason) == false){
            $this->addFlash('danger', $this->translator->trans($reason));
            return $this->redirectToRoute('detail_object',array('id' =>$id) );
        }
        
        // schleichende Migration von alten Datentraeger Objekten
        if($datentraeger == null && 
            ( $object->getKategorie() == Objekt::KATEGORIE_DATENTRAEGER ||
                $object->getKategorie() == Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER )
          ){
            $datentraeger = new Datentraeger();
            $datentraeger->setBarcode($id);
        }
       
        
        // Ab hier hat jeder Datentraeger (Asservat HDD, HDD) einen Eintrag in
        // der Datentraegertabelle
        
        
        $newVerwendung = "";
                
        $changeform = $this->get_EditObjectForm($object, $datentraeger, null)->getForm();
        
        $name = $object->getName();
        $verwendung = $object->getVerwendung();
        
        $changeform->handleRequest($request);
        
        if ($changeform->isSubmitted() && $changeform->isValid()) {
                  
            $editablefields = ["Name"       => array($object->getName()      ,$changeform->getData()['name']),
                               "Verwendung" => array($object->getVerwendung(),$changeform->getData()['verwendung']),
                               "Notiz"     => array($object->getNotiz() ,$changeform->getData()['notiz'])];
            
            
            foreach($editablefields as $fieldname=>$oldAndNewValues){
                $newVerwendung = $this->check_edited_fields($newVerwendung,
                                                        $fieldname,
                                                        $oldAndNewValues[0],
                                                        $oldAndNewValues[1]);
            }
            
            
            if($datentraeger != null)
            {
                $editablefields = ["Bauart"        => array($datentraeger->getBauart()    ,$changeform->getData()['bauart']),
                                   "Formfaktor"    => array($datentraeger->getFormfaktor(),$changeform->getData()['formfaktor']),
                                   "Groesse"       => array($datentraeger->getGroesse()   ,$changeform->getData()['groesse']),
                                   "Hersteller"    => array($datentraeger->getHersteller(),$changeform->getData()['hersteller']),
                                   "Modell"        => array($datentraeger->getModell()    ,$changeform->getData()['modell']),
                                   "Seriennummer"  => array($datentraeger->getSN()        ,$changeform->getData()['sn']),
                                   "Produktnummer" => array($datentraeger->getPN()        ,$changeform->getData()['pn']),
                                   "Anschluss"     => array($datentraeger->getAnschluss() ,$changeform->getData()['anschluss'])];
            
                foreach($editablefields as $fieldname=>$oldAndNewValues){
                    $newVerwendung = $this->check_edited_fields($newVerwendung,
                                                            $fieldname,
                                                            $oldAndNewValues[0],
                                                            $oldAndNewValues[1]);
                } 
            }
            // Wenn keine Aenderung festgestellt wurde, 
            // wird auf die Detailseite angesteuert
            if($newVerwendung == ""){
                return $this->redirectToRoute('detail_object',array('id' =>$id) );
            }
            $object->setSystemaktion(true); 
            $this->createNewHistorieEntry($doctrine,$object);
            $object->setSystemaktion(false);
            
            $object->setName($changeform->getData()['name']);
            $object->setVerwendung($changeform->getData()['verwendung']);
            $object->setNotiz($changeform->getData()['notiz']);
            
            if($datentraeger != null)
            {
                $datentraeger->setBauart($changeform->getData()['bauart']);
                $datentraeger->setFormfaktor($changeform->getData()['formfaktor']);
                if($changeform->getData()['groesse'] != ''){
                    $datentraeger->setGroesse($changeform->getData()['groesse']);
                }
                
                $datentraeger->setHersteller($changeform->getData()['hersteller']);
                $datentraeger->setModell($changeform->getData()['modell']);
                $datentraeger->setSN($changeform->getData()['sn']);
                $datentraeger->setPN($changeform->getData()['pn']);
                $datentraeger->setAnschluss($changeform->getData()['anschluss']);
                
            }
            
            
            $em->persist($object);
            if($datentraeger != null){
                $em->persist($datentraeger);
            }
            
	        $this->admiteditedObject($doctrine,$object, $newVerwendung);
	    
	        $em->flush();
            
            return $this->redirectToRoute('detail_object',array('id' =>$id) );  
        }
        else{
             $this->addFlash('info','action.description.edit.object');
        }
        return $this->render('default/edit_object_form.html.twig', [
            'id' => $id,
            'changeform' => $changeform->createView(),
        ]);
    }
    // This function make the historyentry for the edit Action
    private function admiteditedObject(ManagerRegistry $doctrine, $object, $newVerwendung){
        $em = $doctrine->getManager();
        
        $hist = new \App\Entity\Historie_Objekt($object->getBarcode());

        $hist->setFall($object->getFall());
        $hist->setNutzerId($this->getNutzer($doctrine));
        $hist->setReserviertVon($object->getreserviertVon());
        $hist->setStandort($object->getStandort());
        $hist->setZeitstempelumsetzung($object->getZeitstempelumsetzung());
        // Veraenderte Daten
        $hist->setStatusId(Objekt::STATUS_EDITIERT);
        $hist->setVerwendung($newVerwendung);
        $hist->setSystemaktion(true);
        $hist->setZeitstempel(new \DateTime("now"));

        foreach($object->getImages() as $image){
            $hist->addImage($image);
        }

        $em->persist($hist);
        return 0;

    }

    
   
    private function getNutzer(ManagerRegistry $doctrine){
        $em = $doctrine->getManager();
        $usr= $this->getUser();


        return  $em->getRepository(Nutzer::class)->findOneBy(array('id' => $usr->getId())); // muss geklaert werden
    }
    
    
    /*
     * Diese Funktion generiert einen Historieneintrag aus den derzeitigen
     * Informationen des Objektes. DIES MUSS BEI JEDER AENDERUNG DES OBJEKTES
     * AUSGEFUEHRT WERDEN
     */
    private function createNewHistorieEntry(ManagerRegistry $doctrine, Objekt $object){
                
        $em = $doctrine->getManager();
  
        $hist = new \App\Entity\Historie_Objekt($object->getBarcode());
        
        $hist->setFall($object->getFall());
        $hist->setNutzerId($object->getNutzer($doctrine));
        $hist->setReserviertVon($object->getreserviertVon());
        $hist->setStandort($object->getStandort());
        $hist->setStatusId($object->getStatus());
        $hist->setVerwendung($object->getVerwendung());
        $hist->setZeitstempel($object->getZeitstempel());
        $hist->setZeitstempelumsetzung($object->getZeitstempelumsetzung());
        $hist->setSystemaktion($object->GetSystemaktion());
        
        
        foreach($object->getImages() as $image){
            $hist->addImage($image);
        }
        
        $em->persist($hist);
        //$em->flush();
        return 0;
    }
    
    
    /**
     * @Route("/objekt/{id}/nullen", name="null_object")
     */
    public function null_object_action(Request $request,ManagerRegistry $doctrine,$id){
       return $this->changeVeraenderung($request, $doctrine,
                                        $id, 
                                        Objekt::STATUS_GENULLT,
                                        true,
                                        false);
    }
    
    /**
     * @Route("/objekt/{id}/verwenden", name="use_object")
     */
    public function use_object_action(Request $request,ManagerRegistry $doctrine, $id){
        return $this->changeVeraenderung($request, $doctrine, 
                                        $id, 
                                        Objekt::STATUS_IN_VERWENDUNG,
                                        false,
                                        false);
    }
    
    
    /**
     * @Route("/objekt/{id}/vernichtet", name="destroyed_object")
     */
    public function destroyed_object_action(Request $request, ManagerRegistry $doctrine, $id){
        return $this->changeVeraenderung($request, $doctrine,
                                        $id, 
                                        Objekt::STATUS_VERNICHTET,
                                        false,
                                        true,
                                        array(array('warning','warning.object.cant.be.used.anymore')));
    }
    
    /**
     * @Route("/objekt/{id}/uebergeben", name="delivery_object")
     */
    public function delivery_object_action(Request $request, ManagerRegistry $doctrine, $id){
        return $this->changeVeraenderung($request, $doctrine,
                                        $id, 
                                        Objekt::STATUS_AN_PERSON_UEBERGEBEN,
                                        false,
                                        false,
                                        array(array('info','action.description.delivery.object'),)
                                        );
    }
    
    
     /**
     * @Route("/objekt/{id}/verloren", name="lost_object")
     */
    public function lost_object_action(Request $request,ManagerRegistry $doctrine, $id){
        return $this->changeVeraenderung($request,$doctrine,
                                        $id, 
                                        Objekt::STATUS_VERLOREN,
                                        true,
                                        true,
                                        array(array('info','action.description.lost.object'),
                                            array('warning','warning.object.cant.be.used.anymore')));
    }
    
     /**
     * @Route("/objekt/{id}/reservieren", name="reserve_object")
     */
    public function reserve_object_action(Request $request,ManagerRegistry $doctrine,$id){
       return $this->changeVeraenderung($request, $doctrine,
                                        $id, 
                                        Objekt::STATUS_RESERVIERT,
                                        true,
                                        false);
    }
    
    /**
     * @Route("/objekt/{id}/reservierung/aufheben", name="unreserve_object")
     */
    public function unreserve_object_action(Request $request, ManagerRegistry $doctrine, $id){
        return $this->changeVeraenderung($request, $doctrine,
                                         $id, 
                                         Objekt::STATUS_RESERVIERUNG_AUFGEHOBEN,
                                         true,
                                         false);
    }
    
    
    /**
     * @Route("/objekt/{id}/entnehmen", name="pull_out_object")
     */
    public function pull_out_object_action(Request $request, ManagerRegistry $doctrine, $id){
       return $this->changeVeraenderung($request, $doctrine, 
                                        $id, 
                                        Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT,
                                        false,
                                        false,
                                        array(array('info','action.description.pull.out.object'),));
    }
    
    /**
     * @Route("/objekt/{id}/aus/Fall/entfernen", name="remove_from_case_object")
     */
    public function remove_from_case_object_action(Request $request, ManagerRegistry $doctrine, $id){
       return $this->changeVeraenderung($request, $doctrine,
                                        $id, 
                                        Objekt::STATUS_AUS_DEM_FALL_ENTFERNT,
                                        false,
                                        false,
                                       array(array('info','action.description.remove.from.case.object'),) );
    }
    
    
    private function neutralize_object(ManagerRegistry $doctrine,
                            \App\Entity\Objekt $object,
                            $new_verwendung, 
                            $datum){
        
        if($object->getStatus() != Objekt::STATUS_GENULLT){
            $this->alter_object($doctrine,
                $object,
                Objekt::STATUS_GENULLT,
                $new_verwendung,
                $datum,
                true);
        }
        if($object->getStandort() != null){
            $this->alter_object($doctrine,
                $object,
                Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT,
                $new_verwendung,
                $datum,
                true);
        }

        if($object->getFall() != null){
            $this->alter_object($doctrine,
                $object,
                Objekt::STATUS_AUS_DEM_FALL_ENTFERNT,
                $new_verwendung,
                $datum);

        }
        
        
    }
    
    
    /**
     * @Route("/objekt/{id}/neutralisieren", name="neutralize_object")
     */
    public function neutralize_object_action(Request $request, ManagerRegistry $doctrine,$id){
       
        
        return $this->changeVeraenderung($request,$doctrine,
                                        $id, 
                                        Objekt::VSTATUS_NEUTRALISIERT,
                                        false,
                                        false,
                                        array(array('info','action.description.neutralize.object'),));
       
    }
    
    /*
     *  Ueber diese Funktion wird ein neuer eine Seite mit einer Form ausgegeben.
     *  Die Form enthält nur ein Textfeld, um den neuen Status zu begruenden.
     */
    private function changeVeraenderung(Request $request,
                                ManagerRegistry $doctrine,
                                    $id, // Barcode_ID
                             $status_id, // Der neue Status des Objektes
                                $verwendungNullable,// Ob das Textfeld im Formular auch leer sein darf
                                $javascriptalert,
                                $infotext = null){
        
        /*
         *  Es muss bei jedem Aufruf das Objekt geladen werden, um festzustellen,
         *  ob der Status des Objektes nicht bereits geaendert worden ist.
         *  Dies ist notwendig, um Fehlerhafte doppelte Eintragen(Zuruecktaste)
         *  zu unterbinden. 
         */
        $object = $doctrine->getRepository(Objekt::class)->find($id);
        
        if($object == null){
            $this->addFlash('danger','object_was_not_found');
            return $this->redirectToRoute('search_objects'); 
        }
        
        if($object->isObjectWithNewStatusValid($status_id, 
                                            null, 
                                            $reason) == false){
            $this->addFlash('danger', $this->translator->trans($reason));
            return $this->redirectToRoute('detail_object',array('id' =>$id) );
        }
        
        $new_entry = new Objekt();
        $changeform = $this->get_ChangeVerwendungsForm($verwendungNullable,$javascriptalert,$object->getVerwendung());
        
         
        $changeform->handleRequest($request);
        
        if ($changeform->isSubmitted() && $changeform->isValid()) {
            
            
            switch($status_id){
                case Objekt::VSTATUS_NEUTRALISIERT:
                    $this->neutralize_object($doctrine,$object,$changeform->getData()['verwendung'],$changeform->getData()['dueDate']);
                    break;
                case Objekt::STATUS_AN_PERSON_UEBERGEBEN:
                    if($object->isObjectWithNewStatusValid(Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT) == true){
                        $this->alter_object($doctrine, 
                                    $object,
                                    Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT,
                                    "",
                                    $changeform->getData()['dueDate'],
                                    true);
                    }
                    
                    $this->alter_object($doctrine,
                                $object,
                                Objekt::STATUS_AN_PERSON_UEBERGEBEN,
                                $changeform->getData()['verwendung'],
                                $changeform->getData()['dueDate']);
                    
                    break;
                    
                default:
                    $this->alter_object($doctrine,
                                $object,
                                $status_id,
                                $changeform->getData()['verwendung'],
                                $changeform->getData()['dueDate']);
                    break;
            }
            
            
            
            /*
             * Wenn ein Objekt an eine Person uebergeben werden soll und es
             * sich noch in einem Behaelter befindet, soll dieser zuvor 
             * automatisch herausgezogen werden
             */
            /*if($status_id == helper::STATUS_AN_PERSON_UEBERGEBEN &&
                $object->isObjectWithNewStatusValid(helper::STATUS_AUS_DEM_BEHAELTER_ENTFERNT) == true){
                $this->alter_object($object,
                                    helper::STATUS_AUS_DEM_BEHAELTER_ENTFERNT,
                                    "System: Aufgrund von Uebergabe aus dem Behaelter entfernt",
                                    $changeform->getData()['dueDate']);
            }*/
            
            /*$this->alter_object($object,
                                $status_id,
                                $changeform->getData()['verwendung'],
                                $changeform->getData()['dueDate']);
            
            $this->get('session')->getFlashBag()->clear();*/
            return $this->redirectToRoute('detail_object',array('id' =>$id) );  
        }
        elseif($infotext != null){
            
            if(!empty($infotext)){
                
                foreach($infotext as $text ){
                   $this->addFlash($text[0],$text[1]); 
                }
            }
            
        }
        
        return $this->render('default/change_object.html.twig', [
            'id' => $id,
            'changeform' => $changeform->createView(),
        ]);
        
    }
    
    private function alter_object( ManagerRegistry $doctrine, 
                            \App\Entity\Objekt $object,
                                $status_id,
                            $new_verwendung, 
                            $datum, 
                            $isSystemaktion = false){
        
        $usr= $this->getUser();
        $em = $doctrine->getManager();
           
        $this->createNewHistorieEntry($doctrine,$object);
        
        $new_status = $status_id;
        
        $object->setSystemaktion($isSystemaktion);

        $object->setZeitstempel(new \DateTime('now'));
        $object->setZeitstempelumsetzung($datum);
        

        $object->setVerwendung($new_verwendung);
        $object->setStatus($new_status);
        $object->setNutzer($this->getNutzer($doctrine));
        
        // Notwendige Aenderung, um eine Aktion an ein Objekt durchzufuehren
        switch($status_id){
            case Objekt::STATUS_GENULLT:
                $object->flushImages();
                break;
            case Objekt::STATUS_AUS_DEM_FALL_ENTFERNT:
                $object->setFall(null);
                break;
            case Objekt::STATUS_RESERVIERT:
                $object->setReserviertVon($this->getNutzer($doctrine));
                break;
            case Objekt::STATUS_RESERVIERUNG_AUFGEHOBEN:
                $object->setReserviertVon(null);
                break;
            case Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT:
                $object->setStandort(null);
                break;
            
        }
        
        $em->flush();
            
        return null;
    }
    
    
    
     /**
     * @Route("/objekt/{id}/einlegen/in", name="select_object")
     */
    public function select_objects_action(Request $request,ManagerRegistry $doctrine,$id)
    {
        $object = $doctrine->getRepository(Objekt::class)->find($id);
        
        if($object == null){
            $this->addFlash('danger','object_was_not_found');
            return $this->redirectToRoute('search_objects'); 
        }
        
        $status_id = Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT;
        if($object->isObjectWithNewStatusValid($status_id, 
                                            null, 
                                            $reason) == false){
            $this->addFlash('danger',$this->translator->trans($reason));
            return $this->redirectToRoute('detail_object',array('id' =>$id) );
        }
        
        
        
        /*
         * In dieser Funktion werden alle Behaelter dargestellt,
         * wobei diese Filterbar sind, sprich Beispielsweise per
         * Suchbegriff gefunden werden kann.
         * Wenn ein Behaelter gefunden worden ist, wird die Form mit der
         * Begründung aufgerufen.
         */
        $searchform = $this->get_SearchForm();
        
        
        // Mit diesen Befehl wird festgestellt, ob eine Aenderung durchgefuehrt
        // worden ist
        $searchform->handleRequest($request);
        
        $searchword = null;
        $objekte = null;
        
        if ($searchform->isSubmitted() && $searchform->isValid()) {
            $searchword = $searchform->getData()['suchwort'];
        }
        
        /*
         * Wenn das Suchwort leer ist, dann werden alle Behaelter ausgegeben
         */
        if($searchword == null){
            
            
            $em = $doctrine->getManager();
            $query = $em->createQuery('SELECT o '
                    . 'FROM App:Objekt o '
                    . "WHERE o.Kategorie_id =".Objekt::KATEGORIE_BEHAELTER
                    . " AND o.Barcode_id != :barcode "
                    . "AND o.Status_id !=".Objekt::STATUS_VERNICHTET. " "
                    . "AND o.Status_id !=".Objekt::STATUS_VERLOREN. " "
                    . "AND(o.Standort != :barcode OR o.Standort is null)")
                    ->setParameter("barcode", $id);  
            $objekte = $query->getResult();
            
        }
        else{
            $em = $doctrine->getManager();
            $query = $em->createQuery('SELECT o '
                    . 'FROM App:Objekt o '
                    . "WHERE (o.Name like :searchword "
                    . " OR o.Barcode_id like :searchword )"
                    . " AND o.Kategorie_id =".Objekt::KATEGORIE_BEHAELTER
                    . " AND o.Barcode_id != :barcode "
                    . "AND o.Status_id !=".Objekt::STATUS_VERNICHTET. " "
                    . "AND o.Status_id !=".Objekt::STATUS_VERLOREN. " "
                    . "AND(o.Standort != :barcode OR o.Standort is null)")
                    ->setParameter("searchword","%".$searchword."%")
                    ->setParameter("barcode", $id);  
            $objekte = $query->getResult();
        }
        
        return $this->render('default/select_object.html.twig', array(
            'searchform'=> $searchform->createView(),
            'objekte'=> $objekte,
            'objekt_id' => $id,
            'isReversed' => false,
            'title' => "containersummary",
            'forwardaction' => "store_object"
        ));
        
    }
    
    
    
    private function store_object(ManagerRegistry $doctrine,$fromid,$toid,$timestamp,$description){
        
        
        $object = $doctrine->getRepository(Objekt::class)->find($fromid); // Das Objekt, was in den Behaelter hinzugefuegt wird
        $store_object = $doctrine->getRepository(Objekt::class)->find($toid); // Behaelter, wo das Objekt gelagert wird
        
        if($object->getStandort() != null){
            $this->alter_object($doctrine,
                $object,
                Objekt::STATUS_AUS_DEM_BEHAELTER_ENTFERNT,
                "",
                $timestamp,
                true);
        }
        
        
        
        $this->createNewHistorieEntry($doctrine,$object);
        $object->setSystemaktion(false); 
        $em = $doctrine->getManager();
        $new_status = Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT;

        $object->setZeitstempel(new \DateTime('now'));
        $object->setZeitstempelumsetzung($timestamp);
        $object->setStatus($new_status);

        $object->setVerwendung($description);
        $object->setNutzer($this->getNutzer($doctrine));


        $object->setStandort($store_object);


        $em->flush();
    }
    
    
     /**
     * @Route("/objekt/{fromid}/einlegen/in/{toid}", 
      * name="store_object",
      * requirements={"toid": "\w+","fromid": "\w+"})
     */
    public function store_object_action(Request $request,ManagerRegistry $doctrine,$fromid,$toid)
    {
        
        $object = $doctrine->getRepository(Objekt::class)->find($fromid); // Das Objekt, was in den Behaelter hinzugefuegt wird
        
        if($object == null){
            $this->addFlash('danger','object_was_not_found');
            return $this->redirectToRoute('search_objects'); 
        }
        
        $store_object = $doctrine->getRepository(Objekt::class)->find($toid); // Behaelter, wo das Objekt gelagert wird
        
        if($store_object == null){
            $this->addFlash('danger','container_was_not_found');
            return $this->redirectToRoute('detail_object',array('id' =>$fromid) ); 
        }
        
        
        $changeform = $this->get_ChangeVerwendungsForm(true,false,$object->getVerwendung());
        
       
        
        if($object->isObjectWithNewStatusValid(Objekt::STATUS_IN_EINEM_BEHAELTER_GELEGT, 
                                            $store_object, 
                                            $reason) == false){
            $this->addFlash('danger', $this->translator->trans($reason));
            return $this->redirectToRoute('detail_object',array('id' =>$fromid) );
        }
        
        
        $changeform->handleRequest($request);
        
        if ($changeform->isSubmitted() && $changeform->isValid()) {
            
            $this->store_object($doctrine
                                ,$fromid, 
                                $toid,
                                $changeform->getData()['dueDate'],
                                $changeform->getData()['verwendung']);
            
            return $this->redirectToRoute('detail_object',array('id' =>$fromid) );  
        }
        else{
            $this->addFlash("info",$this->translator->trans('action.description.store.object %category% %objectname% %containername%',
                                         array("%objectname%" => $object->getName(),
                                               "%category%" => $this->translator->trans(array_search($object->getKategorie(),Objekt::$kategorienToId)),
                                               "%containername%" => $store_object->getName())));
        }
        return $this->render('default/change_object.html.twig', [
            'id' => $fromid,
            'changeform' => $changeform->createView(),
        ]);
        
    }
    
   
    
    /**
     * @Route("/objekt/{id}/in/fall", name="select_case")
     */
    public function select_case_action(Request $request,ManagerRegistry $doctrine,$id)
    {
        
        /*
         * In dieser Funktion werden alle Faelle dargestellt,
         * wobei diese Filterbar sind, sprich Beispielsweise per
         * Suchbegriff gefunden werden kann.
         */
        $searchform = $this->get_SearchForm();
        
        $searchword = null;
        $searchform->handleRequest($request);
        if ($searchform->isSubmitted() && $searchform->isValid()) {
            $searchword = $searchform->getData()['suchwort'];
            
        }
        
        $em = $doctrine->getManager();
        $cases = null;
        
        if($searchword == null){
            $cases = $doctrine->getRepository(Fall::class)
                    ->findAll();
        }
        else{
            $query = $em->createQuery('SELECT f '
                    . 'FROM App:Fall f '
                    . "WHERE f.Beschreibung like :search "
                    . "OR f.case_id like :search ")
                    ->setParameter('search','%'.$searchword."%");     
            $cases = $query->getResult();
        }
        
        $query = $em->createQuery('SELECT f '
                    . 'FROM App:Objekt o, '
                    . 'App:Fall f '
                    . "WHERE (DATE_DIFF(o.Zeitstempel,:time) = 0 and "
                    . "o.Fall_id = f.id and "
                    . "o.Status_id = ".Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT .") OR DATE_DIFF(f.Zeitstempel_beginn,:time) = 0 and "
                    . "o.Nutzer_id = :user ")
                    ->setParameter("time", new \DateTime('now'))
                    ->setParameter("user", $this->getNutzer($doctrine));
        $todaycases = $query->getResult();
        
        return $this->render('default/select_case.html.twig', array(
            'searchform'=> $searchform->createView(),
            'faelle' => $cases,
            'objektid' => $id,
            'letztefaelle' => $todaycases
        ));
        

    }
    
    private function add_to_case(ManagerRegistry $doctrine,$objectid,$caseid, $timestamp,$newdescription){
       
        $em = $doctrine->getManager();
        $object = $em->getRepository(Objekt::class)->find($objectid);         
        $case = $em->getRepository(Fall::class)->find($caseid);
        
        $this->createNewHistorieEntry($doctrine,$object);
            
        $new_status = Objekt::STATUS_EINEM_FALL_HINZUGEFUEGT;

        $object->setZeitstempel(new \DateTime('now'));
        $object->setStatus($new_status);
        $object->setZeitstempelumsetzung($timestamp);
        $object->setVerwendung($newdescription);
        $object->setNutzer($this->getNutzer($doctrine));


        $object->setFall($case);


        $em->flush();
    }
    
    /**
     * @Route("/objekt/{objectid}/in/fall/{caseid}/hinzufuegen", name="add_to_case",requirements={"caseid"=".+"})
     */
    public function add_to_case_action(Request $request,ManagerRegistry $doctrine, $objectid, $caseid){
        $em = $doctrine->getManager();
        
        $object = $em->getRepository(Objekt::class)->find($objectid); // Das Objekt, was dem Fall hinzugefuegt wird
        
        $changeform = $this->get_ChangeVerwendungsForm(True,TRUE,$object->getVerwendung());
        
        // Falls Objekt nicht mehr aenderbar ist, soll die Aktion nicht mittels
        // manipulierten Anfragen ausgeführt werden
        if($object->getStatus() == Objekt::STATUS_VERNICHTET ||
            $object->getStatus() == Objekt::STATUS_VERLOREN ||
            $object->getFall() != null){
            return $this->redirectToRoute('detail_object',array('id' =>$objectid) );
        }
        
        
                   
        $query = $em->createQuery('SELECT f '
            . 'FROM App:Fall f '
            . 'where f.case_id = :caseid')
               ->setParameter('caseid',$caseid)
                ->setMaxResults(1);
        
        $case = $query->getResult();
        
        if($case != null){
            
            $case = $query->getResult()[0];
            $changeform->handleRequest($request);
        
            if ($changeform->isSubmitted() && $changeform->isValid()) {

                $this->add_to_case($doctrine,
                                    $objectid, 
                                    $case->getId(), 
                                    $changeform->getData()['dueDate'], 
                                    $changeform->getData()['verwendung']);


                return $this->redirectToRoute('detail_object',array('id' =>$objectid) );  
            }
            else{
                 $this->addFlash("info",$this->translator->trans('action.description.add.to.case %category% %objectname% %casename%',
                                             array("%objectname%" => $object->getName(),
                                                   "%category%" => $this->translator->trans(array_search($object->getKategorie(),Objekt::$kategorienToId)),
                                                   "%casename%" => $case->getBeschreibung())));
            }
            return $this->render('default/change_object.html.twig', [
                'id' => $objectid,
                'changeform' => $changeform->createView(),
            ]);
        }
        else{
            $this->addFlash("danger","case_not_found");
            return $this->redirectToRoute('detail_object',array('id' =>$objectid) );
        }
        
        
    }
    
    
    
    
    /*
     *  TODO: Eindeutig besseren Namen fuer diese Funktion finden,
     *        sollte eigentlich nur eine Form zurückgeben, welche nur
     *        Verwendungen aendert.
     */
    
    private function get_ChangeVerwendungsForm($verwendungNullable,$javascriptalert,$previousdescription){
            
        
        if($javascriptalert){
            $form = $this->createFormBuilder(null,array('attr' => array('onsubmit' => "return alertbeforesubmit()"))); 
        }
        else{
            $form = $this->createFormBuilder(); 
        }
        
        if($verwendungNullable == false){
            $form->add('verwendung',  TextareaType::class,array('required' => true,'data' => $previousdescription,'label' => 'desc.usage'));
        }else{
            $form->add('verwendung',  TextareaType::class,array('required' => false,'data' => $previousdescription,'label' => 'desc.ousage'));
        }
                
        $form->add('dueDate', DateTimeType::class,array('label' => 'desc.action.done',
                                                        'required' => true,
                                                        'widget'=> 'single_text',
                                                         #'format' => 'dd.MM.yyyy HH:mm',
                                                        'with_seconds' => true,
                                                        'data' => new \Datetime(),));
        return $form->add('save',SubmitType::class,array('label' => 'label.do.action'))
                    ->getForm();   
    }
    
    private function get_SearchForm(){
        return $this->createFormBuilder(null,array('attr' => array('class' =>'navbar-form navbar-right')))
                ->add("suchwort", \Symfony\Component\Form\Extension\Core\Type\SearchType::class,array('required' => false,'label'=> false))
                ->getForm();
    }
    
    
    /**
     * @Route("/objekt/{id}/upload", name="upload_pic")
     */
    public function upload_pic_action (Request $request,ManagerRegistry $doctrine,$id){
        
        $object = $doctrine->getRepository(Objekt::class)->find($id);
        
        if($object == null){
            $this->addFlash('danger','object_was_not_found');
            return $this->redirectToRoute('search_objects'); 
        }
        
        
        if($object->getPic() != null || $object->getPicpath() != null){
            return $this->redirectToRoute('detail_object',array('id' =>$id) );
        }
        
	// TODO: make list another way 
        $pubpics = array();
        $i = 0;
        if ($handle = opendir($this->getParameter("pic_directory"))) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && $entry != ".gitkeep") {
                    $pubpics[$i++] = $entry;
                }
            }
            closedir($handle);
        }
        
        
        $form = $this->createFormBuilder(null, array("attr" => array("class" => "form")));
        $form->add('pic', FileType::class,array('required' => false,
                                                'constraints' => [
                                                new \Symfony\Component\Validator\Constraints\File([
                                                    "maxSize" => "10M", // 8388608 2M, 10670080 10M, Fehler: non well formed value
                                                    'mimeTypes' => [
                                                        'image/jpeg',
                                                        'image/jpg',
                                                    ],
                                                ])],
                                                'label' => 'label.upload.pic'));
        $form->add('picpublic', CheckboxType::class,array('required' => false,'label' => 'label.pic.to.public'));
        
       
        $form->add('selectpubpic', ChoiceType::class,array('required' => false,
                                                           'placeholder'=> false,
                                                            'expanded' => true,
                                                            'multiple' => false,
                                                            'label_attr' => array('class' => "radio-inline"),
                                                            'choices' => $pubpics,
                                                            'choice_label' => function($pubpics, $key, $index) {
                                                                                        return $index;
                                                            }));
        
        
        
        
        $changeform = $form->add('save',SubmitType::class,array('label' => 'label.do.action'))
                    ->getForm();
        
        $changeform->handleRequest($request);
        
        if ($changeform->isSubmitted() && $changeform->isValid()) {
            
            //$this->get('session')->getFlashBag()->clear();
            
            $picfile = $changeform['pic']->getData();
            $tempfilename  = "";
            $em = $doctrine->getManager();
            
            if($picfile != null){
               
                $image = imagecreatefromjpeg($picfile->getRealPath());
                $tempfilename  = "";
                $quality = 110;
                
                do{
                    if($tempfilename != ""){
                        unlink($tempfilename);
                    }
                    $quality -=10;
                    
                    $tempfilename = tempnam(sys_get_temp_dir(), "uploadpic");
                    imagejpeg($image, $tempfilename,$quality);
                }
                while(filesize($tempfilename)> (3 * 1024 * 1024) && $quality != 10); // Imagesize should be under 3MB

               if($quality == 10){
                   $this->addFlash('danger','error.image.cant.be.saved');

                   return $this->render('default/upload_pic.html.twig', [
                        'id' => $object,
                        'changeform' => $changeform->createView(),
                    ]);

               }


                $picfile  = new \Symfony\Component\HttpFoundation\File\File( $tempfilename,true);
            }
            
            
            if($changeform['picpublic']->getData() != "1"){
                $object->setPic($picfile);
                $object->setPicpath(null);
                $newVerwendung = $this->translator->trans("uploaded.pic.private");                
            }
            else{
                
                $filename = md5(uniqid()).".".$picfile->guessExtension();
                $picfile->move($this->getParameter("pic_directory"),$filename);
                $object->setPicpath($filename);
                //$object->setPic(null);
                $newVerwendung = $this->translator->trans("uploaded.pic.for.other");
            }
            
            if($changeform['selectpubpic']->getData() != "" && 
                $changeform['pic']->getData() == ""){
                $object->setPicpath($changeform['selectpubpic']->getData());
                $newVerwendung = $this->translator->trans("uploaded.pic.from.other");
                //$object->setPic(null);
            }
            
            $em->flush();
            
            if($tempfilename != "" && file_exists($tempfilename)){
                //clear remaining Picture in Temp folder
                unlink($tempfilename);
            }
            
            $this->admiteditedObject($doctrine,$object, $newVerwendung);
            
            
            
            return $this->redirectToRoute('detail_object',array('id' =>$id) );
            
        }
        
        
        $this->addFlash('info','action.upload.pic');
        
        return $this->render('default/upload_pic.html.twig', [
            'id' => $object,
            'changeform' => $changeform->createView(),
        ]);
    }
    
    
    
    
    /**
     * @Route("/objekt/{id}/Asservatenimage/speichern/", name="select_exhibit_hdd_object")
     */
    public function select_exhibit_hdd_objects_action(Request $request,ManagerRegistry $doctrine,$id)
    {
        // Nur zum Ueberpruefen der ID
        $object = $doctrine->getRepository(Objekt::class)->find($id);
        
        if($object == null){
            $this->addFlash('danger','object_was_not_found');
            return $this->redirectToRoute('search_objects'); 
        }
               
        if($object->isObjectWithNewStatusValid(Objekt::STATUS_FESTPLATTENIMAGE_GESPEICHERT, 
                                            null, 
                                            $reason) == false){
            $this->addFlash('danger',$this->translator->trans($reason));
            return $this->redirectToRoute('detail_object',array('id' =>$id) );
        }
        
        
        
        $searchform = $this->get_SearchForm();
        
        
        // Mit diesen Befehl wird festgestellt, ob eine Aenderung durchgefuehrt
        // worden ist
        $searchform->handleRequest($request);
        
        $searchword = null;
        $objekte = null;
        
        if ($searchform->isSubmitted() && $searchform->isValid()) {
            $searchword = $searchform->getData()['suchwort'];
        }
        
        
        switch($object->getKategorie()){
            case Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER:
                $searchkategorie = Objekt::KATEGORIE_DATENTRAEGER;
                $isReversed = true;
                $title = "exhibithddsummary";
                break;
            case Objekt::KATEGORIE_DATENTRAEGER:
                $searchkategorie = Objekt::KATEGORIE_ASSERVAT_DATENTRAEGER;
                $isReversed = false;
                $title = "hddsummary";
                break;
        }
        
        $em = $doctrine->getManager();
        if($searchword == null){
            $query = $em->createQuery('SELECT o '
                    . 'FROM App:Objekt o '
                    . "WHERE o.Kategorie_id = :searchkategorie"
                    . " AND o.Status_id != :status_destroyed"
                    . " AND o.Status_id != :status_lost"
                    . " AND :object not MEMBER OF o.Images"
                    . " AND :object not MEMBER OF o.HDDs")
                    ->setParameter("searchkategorie",$searchkategorie)
                    ->setParameter("status_destroyed",Objekt::STATUS_VERNICHTET)
                    ->setParameter("status_lost",Objekt::STATUS_VERLOREN)
                    ->setParameter(":object",$object);
        }
        else{
            $query = $em->createQuery('SELECT o '
                    . 'FROM App:Objekt o '
                    . "WHERE (o.Name like :searchword"
                    . " OR o.Barcode_id like :searchword)"
                    . " AND o.Kategorie_id = :searchkategorie"
                    . " AND o.Status_id != :status_destroyed"
                    . " AND o.Status_id != :status_lost"
                    . " AND :object not MEMBER OF o.Images"
                    . " AND :object not MEMBER OF o.HDDs")
                    ->setParameter("searchword","%".$searchword."%")
                    ->setParameter("searchkategorie",$searchkategorie)
                    ->setParameter("status_destroyed",Objekt::STATUS_VERNICHTET)
                    ->setParameter("status_lost",Objekt::STATUS_VERLOREN)
                    ->setParameter(":object",$object);
        }
        $objekte = $query->getResult();
        
        return $this->render('default/select_object.html.twig', array(
            'searchform'=> $searchform->createView(),
            'objekte'=> $objekte,
            'objekt_id' => $id,
            'isReversed' => $isReversed,
            'title' => $title,
            'forwardaction' => "save_image_on_hdd"
        ));
        
    }
    
    
     /**
     * @Route("/objekt/{fromid}/Asservatenimage/speichern/von/{toid}/{returnid}", 
      * name="save_image_on_hdd", 
      * requirements={"toid": "\w+","fromid": "\w+","returnid":"0|1"})
     */
    public function save_image_on_hdd_action(Request $request,ManagerRegistry $doctrine,$fromid,$toid,$returnid)
    {
        
        $object = $doctrine->getRepository(Objekt::class)->find($fromid); // Der Datentraeger, wo das Image gespeichert wird
        
        if($object == null){
            $this->addFlash('danger','object_was_not_found');
            return $this->redirectToRoute('search_objects'); 
        }
        $exhibit_object = $doctrine->getRepository(Objekt::class)->find($toid); // Festplattenasservat

        
        if($exhibit_object == null){
            $this->addFlash('danger','exhibit_object_was_not_found');
            return $this->redirectToRoute('detail_object',array('id' =>$fromid) ); 
        }


        $callbackid = ($returnid == 0)? $object->getBarcode():$exhibit_object->getBarcode();
        
        
        if($object->isObjectWithNewStatusValid(Objekt::STATUS_FESTPLATTENIMAGE_GESPEICHERT, 
                                            $exhibit_object, 
                                            $reason) == false){
            $this->addFlash('danger',$this->translator->trans($reason,array("context" => $exhibit_object)));
            return $this->redirectToRoute('detail_object',array('id' =>$callbackid) );
        }
        
        $changeform = $this->get_ChangeVerwendungsForm(true,false,$object->getVerwendung());
        
        // Falls Objekt nicht mehr aenderbar ist, soll die Aktion nicht mittels
        // manipulierten Anfragen ausgeführt werden
        if($object->getStatus() == Objekt::STATUS_VERNICHTET ||
            $object->getStatus() == Objekt::STATUS_VERLOREN ||
            $exhibit_object->getStatus() == Objekt::STATUS_VERNICHTET ||
            $exhibit_object->getStatus() == Objekt::STATUS_VERLOREN){
            return $this->redirectToRoute('detail_object',array('id' =>$callbackid) );
        }
        
        
        
        $changeform->handleRequest($request);
        
        if ($changeform->isSubmitted() && $changeform->isValid()) {
            
            $this->createNewHistorieEntry($doctrine,$object);
            
            $em = $doctrine->getManager();
            $new_status = Objekt::STATUS_FESTPLATTENIMAGE_GESPEICHERT;
            
            $object->setZeitstempel(new \DateTime('now'));
            $object->setZeitstempelumsetzung($changeform->getData()['dueDate']);
            $object->setStatus($new_status);
            
            $object->setVerwendung($changeform->getData()['verwendung']);
            $object->setNutzer($this->getNutzer($doctrine));
            
            
            $object->addImage($exhibit_object);
           
            
            $em->flush();
            
            return $this->redirectToRoute('detail_object',array('id' =>$callbackid) );  
        }
        else{
            $this->addFlash("info",$this->translator->trans('action.description.save.image.on.hdd %objectname% %exhibit_hdd_name%',
                                         array("%objectname%" => $object->getName(),
                                               "%exhibit_hdd_name%" => $exhibit_object->getName())));
        }
        return $this->render('default/change_object.html.twig', [
            'id' => $fromid,
            'changeform' => $changeform->createView(),
        ]);
        
    }
    
    
    
    
}

