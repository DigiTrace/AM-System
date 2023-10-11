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
use Symfony\Component\HttpFoundation\Request;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;

#neu
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Nutzer;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Response;


class AdminController extends AbstractController
{
    
    /**
     * @Route("/admin/nutzeruebersicht", name="usersummary")
     */
    public function usersummaryAction(ManagerRegistry $doctrine,Request $request)
    {        
              
        
        #$em = $this->getDoctrine()->getManager();
        
        #$userrepo = $em->getRepository(Nutzer::class);
        $userrepo = $doctrine->getRepository(Nutzer::class);
        
        $users = $userrepo->findAll();
        
        $form = $this->getform();
        
        
        return $this->render(
            'default/usersummary.html.twig',
            array(
                'users' => $users,
                'form' => $form->createView()
            )
        );
    }
    
    public function getform(){
        return $this->createFormBuilder()
            //->setAction($this->generateUrl('set_subscribe_case_creation')) 
            ->add('user', TextType::class)
            ->getForm();
    }
    
    
    
    
    /**
     * @Route("/admin/setzte/benachrichtigung", name="set_subscribe_case_creation")
     */
    public function setsubscribeUserToCaseCreationAction(Request $request)
    {        
        $form = $this->getform();
        
        $form->handleRequest($request);
 
        if ($form->isValid()) {
            
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository(Nutzer::class)->findOneBy(array("username" => $form->getData()['user'] ));
            
            if($user != null){
                if($user->getNotifyCaseCreation() == true){
                    $user->setNotifyCaseCreation(false);
                }
                else{
                    $user->setNotifyCaseCreation(true);
                }

                $em->flush();

                return new JsonResponse(array('message' => 'Success!'), 200);
            }
        }

        $response = new JsonResponse(
                array(
            'message' => 'Error',
            ), 400);

        return $response;
    }
    
    
    
     /**
     * @Route("/admin/setzte/aktiv", name="set_enable_user")
     */
    public function setEnableUserAction(Request $request)
    {        
        $form = $this->getform();
        
        $form->handleRequest($request);
 
        if ($form->isValid()) {
            
            $em = $this->getDoctrine()->getManager();
            $user = $em->getRepository(Nutzer::class)->findOneBy(array("username" => $form->getData()['user'] ));
            
            if($user != null){
                if($user->getEnabled() == true){
                    $user->setEnabled(false);
                }
                else{
                    $user->setEnabled(true);
                }

                $em->flush();

                return new JsonResponse(array('message' => 'Success!'), 200);
            }
        }

        $response = new JsonResponse(
                array(
            'message' => 'Error',
            ), 400);

        return $response;
    }
    
    
    
    /**
     * @Route("/admin/{name}/deaktivieren", name="deactivate_user")
     */
    public function deactivateUserAction(Request $request,$name)
    {        
        
        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository(Nutzer::class)->findOneBy(array("username" => $name));
        if($user == null){
            $this->addFlash("danger", "user.not.found");
            $this->redirectToRoute("usersummary");
        }
        else{
            $user->setEnabled(false);
            $em->flush();
        }
        return $this->redirectToRoute("usersummary");
    }
    
    /**
     * @Route("/admin/{name}/reaktivieren", name="reactivate_user")
     */
    public function reactivateUserAction(Request $request,$name)
    {        
        
        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository(Nutzer::class)->findOneBy(array("username" => $name));
        if($user == null){
            $this->addFlash("danger", "user.not.found");
            $this->redirectToRoute("usersummary");
        }
        else{
            $user->setEnabled(true);
            $em->flush();
        }
        return $this->redirectToRoute("usersummary");
    }
    
    

    #[Route('/admin/adduser', name: 'Nutzerhinzufuegen')]
    public function AddNutzer(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $nutzer = new Nutzer();



        $addNutzerform = $this->createFormBuilder($nutzer,array('attr' => array('onsubmit' => "return alertbeforesubmit()")))
            ->add("username", TextType::class, array('label' => 'security.adduser.username','required' => true))
            ->add("fullname", TextType::class, array('label' => 'security.adduser.fullname','required' => true))
            ->add("email", TextType::class, array('label' => 'security.adduser.email','required' => true))
            ->add("plainpassword", PasswordType::class, array('label' => 'security.adduser.password','required' => true))
            ->add('save',SubmitType::class)
            ->getForm();


        $addNutzerform->handleRequest($request);
        if ($addNutzerform->isSubmitted() && $addNutzerform->isValid()) {

            # Pruefen, ob der neue Benutzer schon vorhanden ist:
            
            $query =  $entityManager->createQuery("select u from App:Nutzer u "
                                    . "where u.fullname like :fullname "
                                    . "OR u.email like :email "
                                    . "OR u.username like :username ");

            $query->setParameter(":fullname",$nutzer->getFullname());
            $query->setParameter(":username",$nutzer->getUsername());
            $query->setParameter(":email",$nutzer->getEmail());
            $users = $query->getResult();

             
            if (count($users) > 0){
                $this->addFlash('danger',"security.adduser.account.fail.dup.user");
            }
            else{
                $hashednewPassword = $passwordHasher->hashPassword(
                    $nutzer,
                    $nutzer->getPlainPassword()
                );
                $nutzer->setRoles(["ROLE_USER"]);
    
                $nutzer->setPassword($hashednewPassword);
                $entityManager->persist($nutzer);
                $entityManager->flush();
                $this->addFlash('success',"security.adduser.account.created");
            }            
        }

        return $this->render('profile/AddNutzer.html.twig', array(
            'AddNutzerform' => $addNutzerform->createView()
        ));
    }
    
}

