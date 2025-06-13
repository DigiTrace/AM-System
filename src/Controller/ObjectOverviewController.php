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

use App\Entity\Objekt;
use App\Service\ExtendedAssetSearch;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides overview functionality for objects.
 *
 * @author Robert Krasowski
 * @author Ben Brooksnieder
 */
class ObjectOverviewController extends AbstractController
{
    /**
     * Displays FAQ and help page for advanced search.
     */
    #[Route(data: '/objekte/faq', name: 'search_objects_faq')]
    public function searchFaq(): Response
    {
        return $this->render('default/search_objects_faq.twig');
    }

    /**
     * Return list of all objects, with optional search (simple or advanced) applied.
     *
     * @return Response
     */
    #[Route(data: '/objekte', name: 'search_objects')]
    public function objects(
        Request $request,
        SessionInterface $session,
        ExtendedAssetSearch $extendedAssetSearch,
        EntityManagerInterface $entityManager,
        PaginatorInterface $paginator,
    ) {
        $search = null;
        $query = null;

        $form = $this->getObjektSearchForm($session);
        $form->handleRequest($request);

        // if form is submitted, apply form parameters
        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $search = $formData['search'];

            // allowed values for table sizes
            $limit = match (intval($formData['limit'])) {
                default => 25,
                50 => 50,
                100 => 100,
                1000 => 1000,
            };

            $session->set('limit', $limit);
        }

        // no search term provided, default query for listing all objects
        $search ??= $request->get('suche');

        // apply extended asset search to create query
        if ($search) {
            $query = $extendedAssetSearch->generateSearchQuery($search);
            foreach ($extendedAssetSearch->getErrors() as $err) {
                $this->addFlash($err['type'], $err['message']);
            }
        }

        // no search query or parse error, apply default asset listing
        $query ??= $entityManager->createQuery('SELECT asset FROM App:Objekt asset');

        // populate paginator
        $pagination = $paginator->paginate(
            $query, // query
            $request->query->getInt('page', 1), // page number
            $session->get('limit') ?? 25, // limit per page,
            [
                'defaultSortFieldName' => 'asset.barcode_id',
                'defaultSortDirection' => 'asc',
            ]
        );

        // render object table
        return $this->render('default/search_objects.html.twig', [
            'searchform' => $form->createView(),
            'eas_categories' => Objekt::$kategorienToId,
            'eas_status' => Objekt::$statusToId,
            'pagination' => $pagination,
            'regex_single_match' => $extendedAssetSearch::$regex_single_match,
            'regex_multiple_match' => $extendedAssetSearch::$regex_multiple_match,
        ]);
    }

    /**
     * Im Grunde eine Art "API" zum Verwenden des Barcode Scanners
     * Durch das Scannen des jeweiligen Barcodes soll automatisch zur
     * Detailansicht des jeweiligen Objektes geführt wird.
     *
     * @return Response|RedirectResponse
     */
    #[Route(data: '/objekte-scanner', name: 'search_objects_scanner')]
    public function search_objects_scanner(ManagerRegistry $doctrine, Request $request)
    {
        $searchidform = $this->createFormBuilder()
                 ->add('search', TextType::class,
                     ['required' => false,
                         'label' => 'scan_object_with_scanner',
                         'attr' => ['autofocus' => true]])
                ->getForm();

        $searchidform->handleRequest($request);
        $searchword = null;

        if ($searchidform->isSubmitted() && $searchidform->isValid()) {
            $searchword = $searchidform->getData()['search'];
        }

        if (null != $searchword) {
            $em = $doctrine->getManager();
            $object = $em->getRepository(Objekt::class)->find($searchword);

            if ($object) {
                return $this->redirectToRoute('detail_object', ['id' => $object->getBarcode()]);
            } else {
                $this->addFlash('danger', 'object_was_not_found');
            }
        }

        return $this->render('default/search_objects_scanner.html.twig', [
            'suche' => $searchidform->createView(),
        ]);
    }

    /**
     * Create simple form for querying objekte.
     */
    private function getObjektSearchForm(SessionInterface $session): FormInterface
    {
        $builder = $this->createFormBuilder(null, [
            'method' => 'GET',
            'csrf_protection' => false,
            'attr' => ['class' => 'navbar-form navbar-right', 'id' => 'search_form'],
        ]);

        // text field for query
        $builder->add('search', SearchType::class, [
            'required' => false,
            'label' => false,
            'attr' => ['size' => 75],
        ])
        ->setAction($this->generateURL('search_objects'));

        // limit selector
        $builder->add('limit', ChoiceType::class, [
            'choices' => [
                '25' => '25',
                '50' => '50',
                '100' => '100',
                '1000' => '1000',
            ],
            'label' => false,
            'data' => $session->get('limit'),
            'attr' => ['onchange' => 'submit();'],
        ]);

        // invisible submit
        $builder->add('suchen', SubmitType::class, [
            'label' => 'eas.form.search',
            'attr' => ['style' => 'display: none']
        ]);

        // optional extended search form toggle button
        $builder->add('eas', ButtonType::class, [
            'label' => 'eas.form.show',
            'attr' => [
                'type' => 'button',
                'onclick' => "$('#eas_form_container').slideToggle(250)",
            ]
        ]);

        return $builder->getForm();
    }
}
