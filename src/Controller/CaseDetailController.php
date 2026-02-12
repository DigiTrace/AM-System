<?php

namespace App\Controller;

use App\Entity\Asset;
use App\Entity\CaseFile;
use App\Form\CaseType;
use App\Service\EmailNotification;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Ben Brooksnieder
 */
class CaseDetailController extends BaseController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
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
            $this->addFlash('danger', 'case_not_found');

            return $this->redirectToRoute('search_case');
        }

        $previous = $this->entityManager->getRepository(Asset::class)->findPreviouslyInvolvedInCase($case);

        return $this->render('cases/detail_case.html.twig', [
            'fall' => $case,
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
            $this->addFlash('danger', 'case_not_found');

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
    #[Route('/fall/{id}/downloadWord2/', name: 'download_case_word2', requirements: ['id' => '.+'])]
    public function downloadWord(Request $request, Security $security, TranslatorInterface $translator, string $id)
    {
        $case = $this->entityManager->getRepository(CaseFile::class)->findOneBy(['caseId' => $id]);

        // check if case was found
        if (null == $case) {
            $this->addFlash('danger', 'case_not_found');

            return $this->redirectToRoute('search_case');
        }

        /**
         * @var \App\Entity\Nutzer
         */
        $user = $security->getUser();
        $repo = $this->entityManager->getRepository(Asset::class);
        $previousEntrys = $repo->findPreviouslyInvolvedInCase($case);

        $templateData = [
            'case_details' => $translator->trans('case_details %context%', ['%context%' => $case->getcaseid()]),
            'export.docx.header' => $translator->trans('export.docx.header'),
            'caseId' => $translator->trans('caseId'),
            'caseId_text' => $case->getCaseId(),
            'case_description' => $translator->trans('case_description'),
            'case_description_text' => $case->getDescription(),
            'case_dos' => $translator->trans('case_dos'),
            'case_dos_text' => $translator->trans($case->getSecrecy()->value),
            'case_isactiv' => $translator->trans('case_isactiv'),
            'case_isactiv_text' => ($case->isActive() ? 'Ja' : 'Nein'),
            'case_timestamp' => $translator->trans('case_timestamp'),
            'case_timestamp_text' => $case->getOpenedOn()->format("'d.m.y H:i'"),
            'desc.oid' => $translator->trans('desc.oid'),
            'desc.name' => $translator->trans('desc.name'),
            'desc.lstatus' => $translator->trans('desc.lstatus'),
            'desc.last.action.done' => $translator->trans('desc.last.action.done'),
            'desc.container' => $translator->trans('desc.container'),
            'container_listed_objects' => $translator->trans('container_listed_objects'),
            'case_listed_history_objects' => $translator->trans('case_listed_history_objects'),
            'userstamp' => $translator->trans('report.generated.by.user.%user%.on.%time%', ['%user%' => $user->getFullname(), '%time%' => date('d.m.y H:i')]),
        ];

        \PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
        $templateProcessor = new TemplateProcessor($this->getParameter('word_case_file'));

        // generate file name from case id
        $invalidChars = ['/', '\\', ' '];
        $filename = str_replace($invalidChars, '_', $case->getCaseId()).'docx';
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
        $templateProcessor->cloneRow('Mdesc.oid.text', $previousCount);

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
}
