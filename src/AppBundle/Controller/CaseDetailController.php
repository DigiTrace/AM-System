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

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

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
        
        
        return $this->render('default/detail_case.html.twig', ['fall' => $case]);
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
}}