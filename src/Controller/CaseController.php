<?php

namespace App\Controller;

use App\Entity\Asset;
use App\Entity\CaseFile;
use App\Form\CaseType;
use App\Form\Type\EntitySearchType;
use App\Service\EmailNotification;
use App\Service\ExtendedCaseSearch;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Ben Brooksnieder
 */
class CaseController extends BaseController
{

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Case overview with search function.
     */
    #[Route("/faelle", name:"search_case")]
    public function search_case(
        Request $request, 
        SessionInterface $session,
        PaginatorInterface $paginator, 
        ExtendedCaseSearch $extendedCaseSearch,
    )
    {
        $search = null;
        $query = null;

        $form = $this->createForm(EntitySearchType::class, null, [
            'limit' => $session->get('limit'),
            'show_extended_search' => true,
        ]);
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

            // update session search limit
            $session->set('limit', $limit);
        }
        
        // no search term provided, default query for listing all objects
        $search ??= $request->attributes->get('suche');
        $search ??= $request->attributes->get('search');
        
        // apply extended case search to create query
        if ($search) {
            $query = $extendedCaseSearch->generateSearchQuery($search);
            foreach ($extendedCaseSearch->getErrors() as $err) {
                $this->addFlash($err['type'], $err['message']);
            }
        }

        // no search query or parse error, apply default case listing
        if ($query === null) {
            $repo = $this->entityManager->getRepository(CaseFile::class);
            $query = $repo->createQueryBuilder('caseFile')->getQuery();
        }
        
        // populate paginator
        $pagination = $paginator->paginate(
            $query, // query
            $request->query->getInt('page', 1), // page number
            $session->get('limit') ?? 25, // limit per page,
            [
                'defaultSortFieldName' => 'caseFile.openedOn',
                'defaultSortDirection' => 'desc',
            ]
        );        
        
        return $this->render('cases/search.html.twig', [
            'form' => $form->createView(),
            'pagination' => $pagination
        ]);
    }

    /**
     * Add new case.
     */
    #[Route('/fall/anlegen', name: 'add_case')]
    public function add(Request $request, Security $security, EmailNotification $emailNotification)
    {
        $form = $this->createForm(CaseType::class, null, [
            'showOpenedOn' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var CaseFile
             */
            $case = $form->getData();

            $this->entityManager->persist($case);
            $this->entityManager->flush();

            $this->addFlash('success', 'case.add.success');
            $emailNotification->notifyCaseCreation($case, $security->getUser());

            return $this->redirectToRoute('detail_case', ['id' => $case->getCaseId()]);
        } else {
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('cases/add.html.twig', [
            'form' => $form->createView(),
        ]);
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
            $this->addFlash('danger', 'case.error.not_found');

            return $this->redirectToRoute('search_case');
        }

        $previous = $this->entityManager->getRepository(Asset::class)->findPreviouslyInvolvedInCase($case);

        return $this->render('cases/details.html.twig', [
            'case' => $case,
            'history_assets' => $previous,
        ]);
    }

    /**
     * Edit case form.
     */
    #[Route('/fall/{id}/aktualisieren/', name: 'update_case', requirements: ['id' => '.+'])]
    public function update(Request $request, Security $security, EmailNotification $emailNotification, string $id)
    {
        $case = $this->entityManager->getRepository(CaseFile::class)->findOneBy(['caseId' => $id]);

        // check if case was found
        if (null == $case) {
            $this->addFlash('danger', 'case.error.not_found');

            return $this->redirectToRoute('search_case');
        }

        $form = $this->createForm(CaseType::class, $case, [
            'closedOn_not_before' => $case->getOpenedOn(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var CaseFile
             */
            $case = $form->getData();

            $this->entityManager->persist($case);
            $this->entityManager->flush();

            $this->addFlash('success', 'case.edit.success');
            $emailNotification->notifyCaseAlteration($case, $security->getUser());

            return $this->redirectToRoute('detail_case', ['id' => $case->getCaseId()]);
        } else {
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('cases/edit.html.twig', [
            'form' => $form->createView(),
            'case' => $case,
        ]);
    }

    /**
     * Download case details as word document.
     */
    #[Route('/fall/{id}/downloadWord/', name: 'download_case_word', requirements: ['id' => '.+'])]
    public function downloadWord(Request $request, Security $security, TranslatorInterface $translator, string $id)
    {
        $case = $this->entityManager->getRepository(CaseFile::class)->findOneBy(['caseId' => $id]);

        // check if case was found
        if (null == $case) {
            $this->addFlash('danger', 'case.error.not_found');

            return $this->redirectToRoute('search_case');
        }

        /**
         * @var \App\Entity\Nutzer
         */
        $user = $security->getUser();
        $repo = $this->entityManager->getRepository(Asset::class);
        $previousEntrys = $repo->findPreviouslyInvolvedInCase($case);

        $templateData = [
            'case_details' => $translator->trans('case.download_word.details', ['%case%' => $case->getcaseid()]),
            'export.docx.header' => $translator->trans('export.docx.header'),
            'case_id' => $translator->trans('case.attr.case_id'),
            'case_id_text' => $case->getCaseId(),
            'case_description' => $translator->trans('case.attr.description'),
            'case_description_text' => $case->getDescription(),
            'case_dos' => $translator->trans('case.attr.secrecy'),
            'case_dos_text' => $translator->trans($case->getSecrecy()->value),
            'case_isactiv' => $translator->trans('case.attr.active'),
            'case_isactiv_text' => $translator->trans(($case->isActive() ? 'yes' : 'no')),
            'case_timestamp' => $translator->trans('case.attr.opened_on'),
            'case_timestamp_text' => $case->getOpenedOn()->format("'d.m.y H:i'"),
            'desc.oid' => $translator->trans('asset.attr.barcode'),
            'desc.name' => $translator->trans('asset.attr.name'),
            'desc.lstatus' => $translator->trans('asset.attr.state'),
            'desc.last.action.done' => $translator->trans('asset.attr.last_updated_on'),
            'desc.container' => $translator->trans('asset.attr.location'),
            'container_listed_objects' => $translator->trans('case.download_word.listed_assets'),
            'case_listed_history_objects' => $translator->trans('case.download_word.involved_assets'),
            'userstamp' => $translator->trans('case.download_word.report_by', ['%user%' => $user->getFullname(), '%date%' => date('d.m.y H:i')]),
        ];

        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $templateProcessor = new TemplateProcessor($this->getParameter('word_case_file'));

        // generate file name from case id
        $invalidChars = ['/', '\\', ' '];
        $filename = str_replace($invalidChars, '_', $case->getCaseId()).'.docx';
        $temp_file = tempnam(sys_get_temp_dir(), $filename);

        foreach ($templateData as $key => $value) {
            $templateProcessor->setValue($key, $value);
        }

        $current = $case->getAssets();
        $templateProcessor->cloneRow('Mdesc.oid.text', $current->count());

        for ($i = 1; $i <= $current->count(); ++$i) {
            $asset = $current->next();
            $templateProcessor->setValue("Mdesc.oid.text#$i", $asset->getBarcode());
            $templateProcessor->setValue("Mdesc.name.text#$i", $asset->getName());
            $templateProcessor->setValue("Mdesc.lstatus.text#$i", $translator->trans($asset->getState()->toTranslatableString()));
            $templateProcessor->setValue("Mdesc.last.action.done.text#$i", $asset->getLastUpdatedOn()->format('d.m.y H:i'));
            $location = $asset->getLocation() ?? '-';
            if ($location) {
                $location = $location->getBarcode().' '.$location->getName();
            } else {
            }
            $templateProcessor->setValue("Mdesc.container.text#$i", $location);
        }

        $previousCount = count($previousEntrys);
        $templateProcessor->cloneRow('Hdesc.oid.text', $previousCount);

        for ($i = 1; $i <= $previousCount; ++$i) {
            $asset = $previousEntrys[$i - 1];
            $templateProcessor->setValue("Hdesc.oid.text#$i", $asset->getBarcode());
            $templateProcessor->setValue("Hdesc.name.text#$i", $asset->getName());
            $templateProcessor->setValue("Hdesc.lstatus.text#$i", $translator->trans($asset->getState()->toTranslatableString()));
            $templateProcessor->setValue("Hdesc.last.action.done.text#$i", $asset->getLastUpdatedOn()->format('d.m.y H:i'));
            $location = $asset->getLocation() ?? '-';
            if ($location) {
                $location = $location->getBarcode().' '.$location->getName();
            } else {
            }
            $templateProcessor->setValue("Hdesc.container.text#$i", $location);
        }

        $templateProcessor->saveAs($temp_file);
        $file = new File($temp_file);

        return $this->file($file,$filename);
    }

    /**
     * Display case search FAQ
     */
    #[Route("/faelle/faq", name:"search_cases_faq")]
    public function searchFaq()
    {        
        return $this->render('default/search_cases_faq.twig');
    }
}
