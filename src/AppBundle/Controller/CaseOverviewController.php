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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use AppBundle\Entity\Fall;

class CaseOverviewController extends Controller
{
    
    /**
     * @Route("/faelle", name="search_case")
     */
    public function search_case(Request $request)
    {
        /*
         * In dieser Funktion werden alle Faelle dargestellt,
         * wobei diese Filterbar sind, sprich Beispielsweise per
         * Suchbegriff gefunden werden kann.
         */
        $searchform = $this->get_SearchForm();
        $searchword = null;
        
        
        if ($this->get('session')->get('anzahleintraege') == null){
            $this->get('session')->set('anzahleintraege',25);
        }
        
        
        $searchform->handleRequest($request);
        if ($searchform->isSubmitted() && $searchform->isValid()) {
            $searchword = $searchform->getData()['suchwort'];
            $anzahleintraege = $searchform->getData()['anzahleintraege'];
            $this->get('session')->set('anzahleintraege', $anzahleintraege);
        }
        
        $query = $this->get_case_list($searchword);
        
        
        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $this->get('session')->get('anzahleintraege')/*limit per page*/ ,
            array(
                'defaultSortFieldName' => 'f.Zeitstempel_beginn',
                'defaultSortDirection' => 'desc',
            )
        );
        
        
        return $this->render('default/search_cases.html.twig', array(
            'searchform'=> $searchform->createView(),
            'pagination' => $pagination
        ));
    }
    
    
     private function get_SearchForm(){
        $searchform = $this->createFormBuilder(null,
                        array('method'=>'GET', 
                              'csrf_protection' => false, 
                              'attr' => array('class' => 'navbar-form navbar-right')))
                ->add("suchwort", TextType::class,array(
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
        
        
        return $searchform->getForm();
    }
    
    
    private function get_case($case){
       
        $em = $this->getDoctrine()->getManager();
                
        // Suche nach neuen Ids
        $query = $em->createQuery('SELECT f '
            . 'FROM AppBundle:Fall f '
            . 'where f.case_id like :caseid')
               ->setParameter('caseid',"%".$case->getCaseId()."%")
                ->setMaxResults(1);
        
        
        return $query->getResult();
    }
    
    
    /**
     * @Route("/fall/anlegen", name="add_case")
     */
    public function add_case(Request $request, \Swift_Mailer $mailer)
    {
	
        $error = "";
        $new_case = new Fall();
        $addform = $this->createFormBuilder($new_case,array('attr' => array('onsubmit' => "return alertbeforesubmit()")))
                ->add("case_id", TextType::class, array('label' => 'case_id','required' => true))
                ->add('beschreibung',  TextareaType::class,array('label' => 'case_description'))
                ->add('save',SubmitType::class,array('label' => 'add_new_case'))
                ->getForm();
        
        $addform->handleRequest($request);

        if ($addform->isSubmitted() && $addform->isValid()) {
            if($this->get_case($new_case) == null){
                
            
                $em = $this->getDoctrine()->getManager();
                
                // Suche nach neuen Ids
                /*$query = $em->createQuery('SELECT f '
                    . 'FROM AppBundle:Fall f '
                    . "order by f.newid desc")
                        ->setMaxResults(1);*/
                
                // Wenn kein alternative Id vorhanden ist, wird neu Initialisiert
                /*if($query->getResult() != null){
                    if($query->getResult()[0]->getNewId() == null){
                        $newid = 1;
                    }
                    else{
                        $newid = $query->getResult()[0]->getNewId() + 1;
                    }
                }
                else{
                    $newid = 0;
                }
                
                $new_case->setId(strval($newid));
                $new_case->setNewId($newid);*/
		
                $em->persist($new_case);
                $em->flush();
                
                
                $this->notifyUserAboutCaseCreation($new_case,$mailer);
                
                return $this->redirectToRoute('search_case');
            }
            else{
                $this->addFlash('danger','case_id_already_used');
            }
            
        }
        return $this->render('default/add_case_form.html.twig', array(
            'addform' => $addform->createView()
        ));
    }
    
    public function notifyUserAboutCaseCreation($new_case,$mailer){
        
        // Get User who created the Case
        $em = $this->getDoctrine()->getManager();
        $usr= $this->get('security.token_storage')->getToken()->getUser();
        $calleduser  =  $em->getRepository('AppBundle:Nutzer')->findOneBy(array('id' => $usr->getId())); 
        
        
        // Get Users to Notify
        $query =  $em->createQuery("select u from AppBundle:Nutzer u "
                                 . "where u.notifyCaseCreation = true");
        $users = $query->getResult();
        //$bcclist = array();
        
        $subject = $this->get('translator')->trans("email_case_was_created_subject");
        
        
        foreach($users as $tonotifyuser){
            //array_push($bcclist, $tonotifyuser->getEmail());
            
            $message = (new \Swift_Message($subject))
            ->setFrom($this->getParameter("mailer_resetting_host"))
            ->setTo($tonotifyuser->getEmail());
            
            $message->setBody(
               $this->renderView(
                   // app/Resources/views/Emails/registration.html.twig
                   'emails/notifyCaseCreation.html.twig',
                   array('name' => $tonotifyuser->getUsername(),
                         'calleduser' => $calleduser->getUsername(),
                         'caseid' => $new_case->getCaseId())
               ),
               'text/html'
           );
           
            $mailer->send($message);
        }
        //$message->setBcc($bcclist);
    }
    
    
    
    private function get_case_list($searchword){
        
        if($searchword == null){
            $em    = $this->get('doctrine.orm.entity_manager');
            $dql   = "SELECT f FROM AppBundle:Fall f";
            $query = $em->createQuery($dql);
        }
        else{
            $em = $this->getDoctrine()
                    ->getManager();
            $query = $em->createQuery('SELECT f '
                    . 'FROM AppBundle:Fall f '
                    . "WHERE f.Beschreibung like :search "
                    . "OR f.case_id like :search ")
                    ->setParameter('search',"%".$searchword."%");
        }
        return $query;
    }
}
