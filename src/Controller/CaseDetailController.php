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
use App\Entity\Asset;
use App\Entity\CaseFile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;



# Neu
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
// Include the requires classes of Phpword



use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\File\File;
use App\Entity\Nutzer;


use PhpOffice\PhpWord\TemplateProcessor;


class CaseDetailController extends AbstractController {
    
    public function __construct(private EntityManagerInterface $entityManager){}




    private function get_case($id) {
        $em = $this->getDoctrine()->getManager();
         
        $query = $em->createQuery('SELECT f '
            . 'FROM App:CaseFile f '
            . 'where f.caseId = :caseid ')
               ->setParameter('caseid',$id)
                ->setMaxResults(1);
        
        if(empty($query->getResult())){
            return null;
        }
        else{
            return $query->getResult()[0];
        }
        
        
    }

    public function notifyUserAboutCaseAlteration(ManagerRegistry $doctrine,$case,$mailer,$security){
        
        // Get User who created the Case
        $em = $doctrine->getManager();
        $usertoken = $security->getToken();
        
        
        $usr = $usertoken->getUser();
        $calleduser  =  $em->getRepository(Nutzer::class)->findOneBy(array('id' => $usr->getId())); 
        
        
        // Get Users to Notify
        $query =  $em->createQuery("select u from App:Nutzer u "
                                . "where u.notifyCaseCreation = true");
        $users = $query->getResult();
        
        
        
        foreach($users as $tonotifyuser){
            $subject = $this->translator->trans("email_case_was_altered_subject", locale:$tonotifyuser->getLanguage());
        
            $message = (new TemplatedEmail())
            ->subject($subject)
            ->from($_ENV["mailer_resetting_host"])
            ->to($tonotifyuser->getEmail());
            

            $message->htmlTemplate('emails/notifyCaseAlteration.html.twig');
            $message->context([
                'name' => $tonotifyuser->getFullname(),
                'calleduser' => $calleduser->getFullname(),
                'caseid' => $case->getCaseId(),
                'user_locale' => $tonotifyuser->getLanguage()
            ]);
        
            $mailer->send($message);
        }
        
    }

    /**
     * Case details page.
     */
    #[Route('/fall/{id}/anzeigen/', name: 'detail_case', requirements: ['id' => '.+'])]
    public function details(Request $request, string $id)
    {
        $case = $this->entityManager->getRepository(CaseFile::class)->findOneBy(['caseId' => $id]);

        // check if case was found
        if (null == $case) {
            $this->addFlash('danger', 'case_not_found');

            return $this->redirectToRoute('search_cases');
        }

        $previous = $this->entityManager->getRepository(Asset::class)->findPreviouslyInvolvedInCase($case);

        return $this->render('cases/detail_case.html.twig', [
            'fall' => $case,
            'history_assets' => $previous,
        ]);
    }
    
    // Erzeugen eines Dateinamens fuer den Export von Faellen.
    // Die Funktion behandelt moegliche Sonderfaelle
    private function generateFilename($stringcaseid){
        // Zeichen, welche sowohl Serverseitig, als auch Clientseitig Probleme erzeugen koennen
        // werden in dieser Funktion ersetzt durch '_'
        $invalidChars=['/','\\'];
        $tempstr=$stringcaseid;
        foreach($invalidChars as $char){
            $tempstr=str_replace($char,"_",$tempstr);
        }
        return $tempstr.".docx";
    }
    

    /**
     * @Route("/fall/{id}/aktualisieren/", name="update_case", requirements={"id"=".+"})
     */
    public function update_case(Request $request, $id,Security $security, MailerInterface $mailer,ManagerRegistry $doctrine) {
       
        $case = $this->get_case($id);
        
        if ($case == null) {
            $this->addFlash('danger','case_not_found');
             return $this->redirectToRoute('search_case');
        }

        

        $changeform = $this->createFormBuilder($case, array('attr' => array('onsubmit' => "return alertbeforesubmit()")))
                ->add("caseId", TextType::class, array('label' => 'caseId', 'required' => true))
                ->add('description', TextareaType::class, array('label' => 'case_description'))
                ->add('active', CheckboxType::class, array('label' => 'case_isactiv','required' => false))
                ->add('save', SubmitType::class, array('label' => 'button_update_case'))
                ->getForm();

        $changeform->handleRequest($request);

        if ($changeform->isSubmitted() && $changeform->isValid()) {
                $this->notifyUserAboutCaseAlteration($doctrine,$case,$mailer,$security);
                $em = $doctrine->getManager();
                $em->flush();
                
                return $this->redirectToRoute('detail_case',array('id' =>$id) );
        }
        return $this->render('default/update_case.html.twig',
                ['changeform' => $changeform->createView()] );
        
    }
    
    
    /**
     * @Route("/fall/{id}/downloadWord/", name="download_case_word", requirements={"id"=".+"})
     */
    public function download_case_word(Request $request, TranslatorInterface $translator, $id) {
       
        $case = $this->get_case($id);
        
        if ($case == null) {
            $this->addFlash('danger','case_not_found');
             return $this->redirectToRoute('search_case');
        }
        
        $em = $this->getDoctrine()->getManager();

        
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        

        $assetRepository = $this->entityManager->getRepository(Asset::class);
        $previousEntrys = $assetRepository->findPreviouslyInvolvedInCase($case);
        
        $user = $em->getRepository(Nutzer::class)->findOneBy(array('id' => $usr->getId())); // muss geklaert werden
        
        $replaceText=array(
            'case_details' => $translator->trans('case_details %context%',array("%context%" => $case->getcaseid())),
            'export.docx.header' => $translator->trans('export.docx.header'),
            'caseId' => $translator->trans('caseId'),
            'caseId_text' => $case->getCaseId(),
            'case_description' => $translator->trans('case_description'),
            'case_description_text' => $case->getDescription(),
            'case_dos' => $translator->trans('case_dos'),
            'case_dos_text' => $translator->trans($case->getSecrecy()->value),
            'case_isactiv' => $translator->trans('case_isactiv'),
            'case_isactiv_text' => ($case->isActive() == true ? "Ja" : "Nein"),
            'case_timestamp' => $translator->trans('case_timestamp'),
            'case_timestamp_text' => $case->getOpenedOn()->format("'d.m.y H:i'"),
            'desc.oid'=> $translator->trans('desc.oid'),
            'desc.name'=> $translator->trans('desc.name'),
            'desc.lstatus'=> $translator->trans('desc.lstatus'),
            'desc.last.action.done'=> $translator->trans('desc.last.action.done'),
            'desc.container'=> $translator->trans('desc.container'),
            'container_listed_objects'=> $translator->trans('container_listed_objects'),
            'case_listed_history_objects'=> $translator->trans('case_listed_history_objects'),
            'userstamp'=> $translator->trans('report.generated.by.user.%user%.on.%time%',array("%user%" => $user->getFullname(),'%time%' => date('d.m.y H:i')))
        );

        



        
        
        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        
        $templateProcessor = new TemplateProcessor($this->getParameter("word_case_file"));
        
        
        $fileName = $this->generateFilename($case->getCaseid());
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        
        foreach($replaceText as $key => $value){
           $templateProcessor->setValue($key, $value); 
        }
        
        
        $count_MObjects = $case->getAssets()->count();
        $templateProcessor->cloneRow('Mdesc.oid.text', $count_MObjects);
        
        for($i = 1;$i <= $count_MObjects;$i++){
           $currentObject = ($case->getAssets()[$i-1]);
           $templateProcessor->setValue("Mdesc.oid.text#".$i             ,$currentObject->getBarcode()); 
           $templateProcessor->setValue("Mdesc.name.text#".$i            ,$currentObject->getName());
           $templateProcessor->setValue("Mdesc.lstatus.text#".$i         ,$translator->trans($currentObject->getStatusName()) );
           $templateProcessor->setValue("Mdesc.last.action.done.text#".$i,$currentObject->getZeitstempelumsetzung()->format("d.m.y H:i") );
           if($currentObject->getStandort() != null){
                $templateProcessor->setValue("Mdesc.container.text#".$i       ,$currentObject->getStandort()->getBarcode()." ".$currentObject->getStandort()->getName() );
           }
           else{
               $templateProcessor->setValue("Mdesc.container.text#".$i       , "-");
           }
        }
        
        $count_HObjects = count($previousEntrys);
        $templateProcessor->cloneRow('Hdesc.oid.text', $count_HObjects);
        
        for($i = 1;$i <= $count_HObjects;$i++){
           $currentObject = ($previousEntrys[$i-1]);
           $templateProcessor->setValue("Hdesc.oid.text#".$i             ,$currentObject['barcode_id']); 
           $templateProcessor->setValue("Hdesc.name.text#".$i            ,$currentObject['name']);
           $templateProcessor->setValue("Hdesc.lstatus.text#".$i         ,$translator->trans($currentObject['state']) );
           $templateProcessor->setValue("Hdesc.last.action.done.text#".$i,$currentObject['zeitstempelderumsetzung']->format("d.m.y H:i") );
           if($currentObject['standort'] != null){
                $templateProcessor->setValue("Hdesc.container.text#".$i       ,$currentObject['standort']." ".$currentObject['Standortname']);
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

