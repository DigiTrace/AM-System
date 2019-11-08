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

//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;


// Include the requires classes of Phpword


use PhpOffice\PhpWord\Shared\ZipArchive;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\File;
class CaseDetailController extends Controller {
    
    private function get_case($id) {
        $em = $this->getDoctrine()->getManager();
         
        $query = $em->createQuery('SELECT f '
            . 'FROM AppBundle:Fall f '
            . 'where f.case_id = :caseid ')
               ->setParameter('caseid',$id)
                ->setMaxResults(1);
        
        if(empty($query->getResult())){
            return null;
        }
        else{
            return $query->getResult()[0];
        }
        
        
    }
    
    private function getinvolvedObjectsFromCase($case){
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery('SELECT distinct o.Barcode_id,'
                . 'o.Name,'
                . 'so.Barcode_id AS Standort,'
                . 'o.Status_id,'
                . 'o.Zeitstempelderumsetzung,'
                . 'so.Name AS Standortname  '
                    . 'FROM AppBundle:Objekt o '
                    . 'JOIN AppBundle:Historie_Objekt ho with ho.Barcode_id = o.Barcode_id '
                    . 'LEFT JOIN AppBundle:Objekt so with so.Barcode_id = o.Standort '
                    . "WHERE ho.Fall_id = :case OR "
                    . "o.Fall_id = :case ")
                    ->setParameter("case",$case->getId());  
        return $query->getResult();
        
    }

    /**
     * @Route("/fall/{id}/anzeigen/", name="detail_case", requirements={"id"=".+"})
     */
    public function details_case(Request $request, $id) {
        /*
         * hier wird eines der Faelle im Detail angezeigt,
         * Dadurch erhält man Zugriff auf die fuer den Fall verwendeten
         * Objekte.
         */
        $case = $this->get_case($id);

        
        if (!$case) {
            $this->addFlash('danger','case_not_found');
            return $this->redirectToRoute('search_case');
        }
        
        $history_entrys = $this->getinvolvedObjectsFromCase($case);
        
        
        
        
        return $this->render('default/detail_case.html.twig',
                                    ['fall' => $case,
                                     'historie_objekts' => $history_entrys]);
    }
    
    

    /**
     * @Route("/fall/{id}/aktualisieren/", name="update_case", requirements={"id"=".+"})
     */
    public function update_case(Request $request, $id) {
       
        $case = $this->get_case($id);
        
        if ($case == null) {
            $this->addFlash('danger','case_not_found');
             return $this->redirectToRoute('search_case');
        }
        

        $changeform = $this->createFormBuilder($case, array('attr' => array('onsubmit' => "return alertbeforesubmit()")))
                ->add("case_id", TextType::class, array('label' => 'case_id', 'required' => true))
                ->add('beschreibung', TextareaType::class, array('label' => 'case_description'))
                ->add('istAktiv', CheckboxType::class, array('label' => 'case_isactiv','required' => false))
                ->add('save', SubmitType::class, array('label' => 'button_update_case'))
                ->getForm();

        $changeform->handleRequest($request);

        if ($changeform->isSubmitted() && $changeform->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                
                return $this->redirectToRoute('detail_case',array('id' =>$id) );
        }
        return $this->render('default/update_case.html.twig',
                ['changeform' => $changeform->createView()] );
        
    }
    
    
    /**
     * @Route("/fall/{id}/downloadWord/", name="download_case_word", requirements={"id"=".+"})
     */
    public function download_case_word(Request $request, $id) {
       
        $case = $this->get_case($id);
        
        if ($case == null) {
            $this->addFlash('danger','case_not_found');
             return $this->redirectToRoute('search_case');
        }
        
        $em = $this->getDoctrine()->getManager();
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        
        $history_entrys = $this->getinvolvedObjectsFromCase($case);
        
        $user = $em->getRepository('AppBundle:Nutzer')->findOneBy(array('id' => $usr->getId())); // muss geklaert werden
        
        $replaceText=array(
            'case_details' => $this->get('translator')->trans('case_details %context%',array("%context%" => $case->getcaseid())),
            'export.docx.header' => $this->get('translator')->trans('export.docx.header'),
            'case_id' => $this->get('translator')->trans('case_id'),
            'case_id_text' => $case->getCaseId(),
            'case_description' => $this->get('translator')->trans('case_description'),
            'case_description_text' => $case->getBeschreibung(),
            'case_isactiv' => $this->get('translator')->trans('case_isactiv'),
            'case_isactiv_text' => ($case->istAktiv() == true ? "Ja" : "Nein"),
            'case_timestamp' => $this->get('translator')->trans('case_timestamp'),
            'case_timestamp_text' => $case->getZeitstempel()->format("'d.m.y H:i'"),
            'desc.oid'=> $this->get('translator')->trans('desc.oid'),
            'desc.name'=> $this->get('translator')->trans('desc.name'),
            'desc.lstatus'=> $this->get('translator')->trans('desc.lstatus'),
            'desc.last.action.done'=> $this->get('translator')->trans('desc.last.action.done'),
            'desc.container'=> $this->get('translator')->trans('desc.container'),
            'container_listed_objects'=> $this->get('translator')->trans('container_listed_objects'),
            'case_listed_history_objects'=> $this->get('translator')->trans('case_listed_history_objects'),
            'time' => date('d.m.y H:i'),
            'user' => $user->getUsername()
        );
        
        
        $templateProcessor = new TemplateProcessor($this->getParameter("word_case_file"));
        $fileName=$case->getCaseid().".docx";
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        
        foreach($replaceText as $key => $value){
           $templateProcessor->setValue($key, $value); 
        }
        
        
        $count_MObjects = $case->getObjekte()->count();
        $templateProcessor->cloneRow('Mdesc.oid.text', $count_MObjects);
        
        for($i = 1;$i <= $count_MObjects;$i++){
           $currentObject = ($case->getObjekte()[$i-1]);
           $templateProcessor->setValue("Mdesc.oid.text#".$i             ,$currentObject->getBarcode()); 
           $templateProcessor->setValue("Mdesc.name.text#".$i            ,$currentObject->getName());
           $templateProcessor->setValue("Mdesc.lstatus.text#".$i         ,$this->get('translator')->trans($currentObject->getStatusName()) );
           $templateProcessor->setValue("Mdesc.last.action.done.text#".$i,$currentObject->getZeitstempelumsetzung()->format("d.m.y H:i") );
           if($currentObject->getStandort() != null){
                $templateProcessor->setValue("Mdesc.container.text#".$i       ,$currentObject->getStandort()->getBarcode()." ".$currentObject->getStandort()->getName() );
           }
           else{
               $templateProcessor->setValue("Mdesc.container.text#".$i       , "-");
           }
        }
        
        $count_HObjects = count($history_entrys);
        $templateProcessor->cloneRow('Hdesc.oid.text', $count_HObjects);
        
        for($i = 1;$i <= $count_HObjects;$i++){
           $currentObject = ($history_entrys[$i-1]);
           $templateProcessor->setValue("Hdesc.oid.text#".$i             ,$currentObject['Barcode_id']); 
           $templateProcessor->setValue("Hdesc.name.text#".$i            ,$currentObject['Name']);
           $templateProcessor->setValue("Hdesc.lstatus.text#".$i         ,$this->get('translator')->trans(\AppBundle\Entity\Objekt::getStatusNameFromId($currentObject['Status_id'])) );
           $templateProcessor->setValue("Hdesc.last.action.done.text#".$i,$currentObject['Zeitstempelderumsetzung']->format("d.m.y H:i") );
           if($currentObject['Standort'] != null){
                $templateProcessor->setValue("Hdesc.container.text#".$i       ,$currentObject['Standort']." ".$currentObject['Standortname']);
           }
           else{
               $templateProcessor->setValue("Hdesc.container.text#".$i       , "-");
           }
        }
        
        
        $templateProcessor->saveAs($temp_file);

        $file = new File($temp_file);
        
        return $this->file($file,$fileName);        
    }


}

