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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Entity\Fall;


# NEU 
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Security\Core\Security;
use App\Entity\Nutzer;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;



class CaseOverviewController extends AbstractController
{
    
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @Route("/faelle", name="search_case")
     */
    public function search_case(ManagerRegistry $doctrine, RequestStack $requeststack,PaginatorInterface $paginator,Request $request)
    {
        /*
         * In dieser Funktion werden alle Faelle dargestellt,
         * wobei diese Filterbar sind, sprich Beispielsweise per
         * Suchbegriff gefunden werden kann.
         */
        $searchform = $this->get_SearchForm($requeststack);
        $searchword = null;

        $session=$requeststack->getSession();
        
        
        if ($session->get('anzahleintraege') == null){
            $session->set('anzahleintraege',25);
        }
        
        
        $searchform->handleRequest($request);
        if ($searchform->isSubmitted() && $searchform->isValid()) {
            $searchword = $searchform->getData()['suchwort'];
            $anzahleintraege = $searchform->getData()['anzahleintraege'];
            $session->set('anzahleintraege', $anzahleintraege);
        }
        
        if($searchword == null){
             $searchword =  $request->get("suche");
        }
        
        
        if($searchword == null){
            
            #$em    = $this->get('doctrine.orm.entity_manager');
            $em = $doctrine->getManager();
            $dql   = "SELECT c FROM App:Fall c";
            $query = $em->createQuery($dql);
            
        }
        else{
            $query = $this->create_search_query($doctrine, $searchword);
        } 
        
        
        #$paginator  = $this->get('knp_paginator');
        
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1)/*page number*/,
            $session->get('anzahleintraege')/*limit per page*/ ,
            array(
                'defaultSortFieldName' => 'c.Zeitstempel_beginn',
                'defaultSortDirection' => 'desc',
            )
        );
        
        
        return $this->render('default/search_cases.html.twig', array(
            'searchform'=> $searchform->createView(),
            'pagination' => $pagination
        ));
    }
    
    
     private function get_SearchForm(RequestStack $requeststack){
        $session=$requeststack->getSession();

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
                    'label'=> false,'data'=> $session->get('anzahleintraege'),'attr'=>array('onchange' => 'submit();')));
        
        
        return $searchform->getForm();
    }
    
    
    private function get_case(ManagerRegistry $doctrine,$case){
       
        $em = $doctrine->getManager();
                
        // Suche nach neuen Ids
        $query = $em->createQuery('SELECT f '
            . 'FROM App:Fall f '
            . 'where f.case_id like :caseid')
               ->setParameter('caseid',"%".$case->getCaseId()."%")
                ->setMaxResults(1);
        
        
        return $query->getResult();
    }
    
    
    /**
     * @Route("/fall/anlegen", name="add_case")
     */
    public function add_case(Request $request,ManagerRegistry $doctrine,Security $security, MailerInterface $mailer)
    {
	
        $error = "";
        $new_case = new Fall();
        $dosarray = Fall::getDOSList();
        
        $addform = $this->createFormBuilder($new_case,array('attr' => array('onsubmit' => "return alertbeforesubmit()")))
                ->add("case_id", TextType::class, array('label' => 'case_id','required' => true))
                ->add('beschreibung',  TextareaType::class,array('label' => 'case_description'))
                ->add('save',SubmitType::class,array('label' => 'add_new_case'))
                
                ->add('dos', ChoiceType::class,array('required' => false,
                                                    'placeholder'=> false,
                                                     'expanded' => false,
                                                     'multiple' => false,
                                                     'data' => Fall::DEGREE_OF_SECRECY_CONFIDENTIAL,
                                                     'choices' => $dosarray,
                                                     'choice_label' => function($dosarray, $key, $index) {
                                                                                 return $index;
                                                     }))
                ->getForm();
        
        $addform->handleRequest($request);

        if ($addform->isSubmitted() && $addform->isValid()) {
            if($this->get_case($doctrine,$new_case) == null){
                
            
                $em = $doctrine->getManager();
                
                // Suche nach neuen Ids
                /*$query = $em->createQuery('SELECT f '
                    . 'FROM App:Fall f '
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
                
                
                $this->notifyUserAboutCaseCreation($doctrine,$new_case,$mailer,$security);
                
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
    
    public function notifyUserAboutCaseCreation(ManagerRegistry $doctrine,$new_case,$mailer,$security){
        
        // Get User who created the Case
        $em = $doctrine->getManager();
        #$usr= $this->get('security.token_storage')->getToken()->getUser();
        
        $usertoken = $security->getToken();
        
        
        # TEMPORAR, da FOS nicht funktioniert
        if($usertoken  != null )
        {
            $usr = $usertoken->getUser();
            $calleduser  =  $em->getRepository(Nutzer::class)->findOneBy(array('id' => $usr->getId())); 
            
            
            // Get Users to Notify
            $query =  $em->createQuery("select u from App:Nutzer u "
                                    . "where u.notifyCaseCreation = true");
            $users = $query->getResult();
            //$bcclist = array();
            
            $subject = $this->translator->trans("email_case_was_created_subject");
            
            
            foreach($users as $tonotifyuser){
                
                $message = (new TemplatedEmail())
                ->subject($subject)
                ->from($_ENV["mailer_resetting_host"])
                ->to($tonotifyuser->getEmail());
                

                $message->htmlTemplate('emails/notifyCaseCreation.html.twig');
                $message->context([
                    'name' => $tonotifyuser->getFullname(),
                    'calleduser' => $calleduser->getFullname(),
                    'caseid' => $new_case->getCaseId()
                ]);
            
                $mailer->send($message);
            }
        }
    }
    
    
    
    
    
    private function create_search_query(ManagerRegistry $doctrine,$searchword){
        
      $repository = $doctrine->getRepository(Fall::class); 
      
      $query = $repository->createQueryBuilder('c');
      $query->leftjoin("App:Objekt"         , "o","WITH" ,"c.id = o.Fall_id");
      $query->leftjoin("App:Historie_Objekt", "ho","WITH","c.id = ho.Fall_id ");
      
      

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
                
        
        $simplewhereconditions= array("caseid" => "c.case_id",
                                "desc" => "c.Beschreibung");


        
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
                   
                    
                        
                if($skipparameter != null){
                    array_push($usedSearchParameters,
                               array($match['kriterium'],
                                   ($skipparameter)? "true":"false")
                              );
                    continue;
                }
            }
                    
            
            
            switch($match["kriterium"]):
                case "caseid":
                case "desc":
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
                case "casebegin":
                    $success = preg_match('/(?<operator>[!<>]?)(?<day>[\d]{2})[-\.]{1}(?<month>[\d]{2})[-\.]{1}(?<year>[\d]{4})/',
                                    $match['value'],
                                    $mdatematch);
                    
                    if($success == true){

                        if(checkdate($mdatematch['month'], $mdatematch['day'], $mdatematch['year']) == true){
                            
                            switch ($mdatematch['operator']):
                            case "<":
                            case ">":
                                $query->andwhere("DATE_DIFF(c.Zeitstempel_beginn,:parameter".$indexparameter.") ".$mdatematch['operator']." 0 ");
                                break;
                            case "!":
                                $query->andwhere("DATE_DIFF(c.Zeitstempel_beginn,:parameter".$indexparameter.") != 0 ");
                                break;
                            case "":
                                $query->andwhere("DATE_DIFF(c.Zeitstempel_beginn,:parameter".$indexparameter.") = 0 ");
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
                            $this->translator->trans('criteria.was.not.found %criteria%',array("%criteria%" => $errors))
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
            $em = $doctrine->getManager();
            $query = $em->createQuery('SELECT c '
                    . 'FROM App:Fall c '
                    . "WHERE c.Beschreibung like :search "
                    . "OR c.case_id like :search ")
                    ->setParameter('search',"%".$searchword."%");  
        }
        return $query;
    }
    
    /**
     * @Route("/faelle/faq", name="search_cases_faq")
     */
    public function search_faqAction(Request $request)
    {        
        return $this->render('default/search_cases_faq.twig');
    }
}
