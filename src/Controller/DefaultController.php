<?php
/**
 * AM-System
 * Copyright (C) 2019 Robert Krasowski
 * This program was created during an internship at DigiTrace GmbH
 * Read LIZENZ.txt for full notice
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author Robert Kraswoski
 */

namespace App\Controller;

use App\Entity\Nutzer;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * 
 */
class DefaultController extends AbstractController
{
    
    
    /**
     * Show dashboard.
     */
    #[Route('/', name: 'homepage')]
    public function indexAction(Request $request)
    {
        /*
         * Startseite des Managementsystems:io
         */
        return $this->render('default/index.html.twig');
    }

 
 
    
    /**
     * @Route("/changelog", name="changelog")
     */
    #[Route('/changelog', name: 'changelog')]
    public function showChangelogAction(Request $request)
    {
        
        return $this->render('default/changelog.html.twig');
    }
   
    
     /**
     * @Route("/profil/change-language", name="change_language")
     */
    public function change_language(Request $request, RequestStack $requestStack){
        
        $user= $this->get('security.token_storage')->getToken()->getUser();
          
        
        $form =  $this->createFormBuilder(null,array())
                ->add('language', ChoiceType::class,[
                        'label' => 'supported_languages',
                        'choices' => array(
                    'language_eng' => 'en',
                    'language_de' => 'de',
                )])
                ->add('save',SubmitType::class,array('label' => 'action.change.language'))
                ->getForm();
        
        
        $form->handleRequest($request);

        
        if (    $form->isSubmitted() && 
                $form->isValid() ) {
            
            $em = $this->getDoctrine()->getManager();
            
            $nutzer = $em->getRepository(Nutzer::class)->find($user);
            
            $nutzer->SetLanguage($form->getData()["language"]);
            $em->flush();
            

            $session = $requestStack->getSession();
            
            $session->set("_locale",$form->getData()["language"]);
            return $this->redirectToRoute('Nutzerprofil',array() );
        }
        
        return $this->render('default/change_language.html.twig', array(
            'form' => $form->createView(),
        )); 
      } 
     
    
}
