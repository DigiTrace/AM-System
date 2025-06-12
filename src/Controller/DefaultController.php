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

use App\Entity\Fall;
use App\Entity\Nutzer;
use App\Entity\Objekt;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

/**
 * 
 */
class DefaultController extends AbstractController
{
    /**
     * Show dashboard with information about recent cases and reserved objects.
     */
    #[Route('/', name: 'homepage')]
    public function index(Request $request, ManagerRegistry $doctrine)
    {
        // get user
        $user = $this->get('security.token_storage')->getToken()->getUser();

        // get all reserved objects
        $repository = $doctrine->getRepository(Objekt::class);
        $reservedObjects = $repository->findAllReservedByUser($user);

        // get open cases
        $repository = $doctrine->getRepository(Fall::class);
        $cases = $repository->findAllOpen(10);

        return $this->render('default/index.html.twig', [
            'reservedObjects' => $reservedObjects,
            'recentCases' => $cases,
        ]);
    }

    /**
     * Show changelog.
     */
    #[Route('/changelog', name: 'changelog')]
    public function changelog(Request $request)
    {
        return $this->render('default/changelog.html.twig');
    }

    /**
     * Change language view and logic.
     */
    #[Route('/profil/change-language', name: 'change_language')]
    public function changeLanguage(Request $request, RequestStack $requestStack)
    {
        // get user
        $user = $this->get('security.token_storage')->getToken()->getUser();

        // create form to select from available languages
        $form = $this->createFormBuilder(null, [])
            ->add('language', ChoiceType::class, [
                'label' => 'supported_languages',
                'choices' => [
                    'language_eng' => 'en',
                    'language_de' => 'de',
                ]])
            ->add('save', SubmitType::class, ['label' => 'action.change.language'])
            ->getForm();

        // process form
        $form->handleRequest($request);

        if ($form->isSubmitted()
                && $form->isValid()) {
            // set language
            $em = $this->getDoctrine()->getManager();

            $nutzer = $em->getRepository(Nutzer::class)->find($user);

            $nutzer->SetLanguage($form->getData()['language']);
            $em->flush();

            $session = $requestStack->getSession();

            $session->set('_locale', $form->getData()['language']);

            // return to user page
            return $this->redirectToRoute('Nutzerprofil', []);
        }

        // show form
        return $this->render('default/change_language.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
