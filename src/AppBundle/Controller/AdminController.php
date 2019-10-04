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
use Symfony\Component\HttpFoundation\Request;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends Controller
{
    
    /**
     * @Route("/admin/nutzeruebersicht", name="usersummary")
     */
    public function usersummaryAction(Request $request)
    {        
              
        
        $em = $this->getDoctrine()->getManager();
        
        $users = $em->getRepository("AppBundle:Nutzer")->findAll();
        
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
            $user = $em->getRepository("AppBundle:Nutzer")->findOneBy(array("username" => $form->getData()['user'] ));
            
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
            $user = $em->getRepository("AppBundle:Nutzer")->findOneBy(array("username" => $form->getData()['user'] ));
            
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
        
        $user = $em->getRepository("AppBundle:Nutzer")->findOneBy(array("username" => $name));
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
        
        $user = $em->getRepository("AppBundle:Nutzer")->findOneBy(array("username" => $name));
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
    
    
   
    
}

