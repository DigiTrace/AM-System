<?php

namespace App\Controller;

use App\Action\Asset as Actions;
use App\Action\Asset\AssetAction;
use App\Entity\Asset;
use App\Entity\AssetHistory;
use App\Enum\AssetCategory as Category;
use App\Enum\AssetState as State;
use App\Form\Asset\AddType;
use App\Form\Asset\MultiActionType;
use App\Form\Asset\SingleActionType;
use App\Form\Asset\UploadPictureType;
use App\Form\Type\EntitySearchType;
use App\Repository\AssetRepository;
use App\Service\AssetActionManager;
use App\Service\ExtendedAssetSearch;
use App\Service\ExtendedCaseSearch;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for managing assets.
 *
 * @author Ben Brooksnieder
 */
class AssetController extends BaseController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Return list of all assets, with optional search (simple or advanced) applied.
     */
    #[Route('/objekte', name: 'search_assets')]
    public function searchAssets(
        Request $request,
        SessionInterface $session,
        PaginatorInterface $paginator,
        ExtendedAssetSearch $extendedAssetSearch,
    ) {
        $search = null;
        $query = null;

        // search form
        $form = $this->createForm(EntitySearchType::class, null, [
            'limit' => $session->get('limit'),
            'show_extended_search' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $formData = $form->getData();
            $search = $formData['search'];

            // allowed values for table sizes
            $limit = match (\intval($formData['limit'])) {
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

        // apply extended asset search to create query
        if ($search) {
            $query = $extendedAssetSearch->generateSearchQuery($search);
            foreach ($extendedAssetSearch->getErrors() as $err) {
                $this->addFlash($err['type'], $err['message']);
            }
        }

        // no search query or parse error, apply default asset listing
        if (null === $query) {
            $repo = $this->entityManager->getRepository(Asset::class);
            $builder = $repo->createQueryBuilder('asset');
            $builder->select('PARTIAL asset.{barcode, category, state, name}');
            // // TODO optimize for doctrine N + 1 querie issues
            // $builder->innerJoin('asset.drive', 'drive');
            // $builder->innerJoin('asset.assetBlob', 'blob');
            $query = $builder;
        }

        // populate paginator
        $pagination = $paginator->paginate(
            $query, // query
            $request->query->getInt('page', 1), // page number
            $session->get('limit') ?? 25, // limit per page,
            [
                'defaultSortFieldName' => 'asset.barcode',
                'defaultSortDirection' => 'asc',
            ]
        );

        // selection form for multiple edits
        $selectionForm = $this->createForm(MultiActionType::class, [], [
            'method' => 'POST',
            'action' => $this->generateUrl('asset_multi_edit'),
            'preview' => true,
        ],
        );

        // render object table
        return $this->render('assets/search.html.twig', [
            'search' => $form->createView(),
            'selection' => $selectionForm->createView(),
            'eas_categories' => Category::cases(),
            'eas_states' => State::cases(),
            'pagination' => $pagination,
            'regex_single_match' => $extendedAssetSearch::$regex_single_match,
            'regex_multiple_match' => $extendedAssetSearch::$regex_multiple_match,
        ]);
    }

    /**
     * Edit multiple assets at once. Might be invoked by asset overview page.
     */
    #[Route('objekte/aendern', name: 'asset_multi_edit')]
    public function multiAction(
        Request $request,
        AssetActionManager $manager,
    ) {
        // populate form and handle request
        $form = $this->createForm(MultiActionType::class, [], []);
        $form->handleRequest($request);

        // show immediate form errors
        $data = $form->getData();
        foreach ($form->getErrors() as $error) {
            $this->addFlash('danger', $error->getMessage());
        }

        // pre validate assets to verify that it is legitimate action
        $violations = [];
        if (!empty($data['assets']) && !empty($data['action'])) {
            $violations = $manager->isValidAction($data['assets'], $data['action']);
        }

        // check if form is valid and submitted by save button (not preview)
        if ($form->get('save')->isClicked()
           && $form->isSubmitted()
           && 0 == $form->getErrors()->count()
           && empty($violations)) {
            // perform action on multiple assets
            $violations = $manager->performAction($data['assets'], $data, $data['action']);

            // action must be valid for all assets
            if (empty($violations)) {
                $this->addFlash('success', 'asset.action.multi_action.success');

                return $this->redirectToRoute('search_assets');
            }

            // else refresh assets
            foreach ($data['assets'] as $asset) {
                $this->entityManager->refresh($asset);
            }
        }

        return $this->render('assets/multi_action.html.twig', [
            'assets' => $data['assets'] ?? [],
            'violations' => $violations,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Im Grunde eine Art "API" zum Verwenden des Barcode Scanners
     * Durch das Scannen des jeweiligen Barcodes soll automatisch zur
     * Detailansicht des jeweiligen Objektes geführt wird.
     */
    #[Route('/objekte-scanner', name: 'scan_assets')]
    public function assetScanner(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('search', TextType::class, [
                'required' => false,
                'label' => 'asset.scanner.form',
                'attr' => ['autofocus' => true],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $searchword = trim($form->getData()['search']);
            if (!empty($searchword)) {
                $asset = $this->entityManager->getRepository(Asset::class)->find($searchword);

                if ($asset) {
                    return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
                }

                $this->addFlash('danger', 'asset.error.not_found');
            }
        }

        return $this->render('assets/scanner.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show form to add new asset or handle new asset form request.
     */
    #[Route('/objekt/anlegen', name: 'add_asset')]
    public function add(Request $request)
    {
        $form = $this->createForm(AddType::class, null, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /**
             * @var Asset
             */
            $asset = $form->getData();
            $asset->setState(State::Added); // TODO Not State::Added?
            $asset->setModifiedBy($this->getUser());
            $asset->setLastUpdatedOn(new \DateTime());

            // add as seperate step
            $case = $asset->getCase();
            $asset->setCase(null);

            $this->entityManager->persist($asset);

            if ($asset->isDrive()) {
                $this->entityManager->persist($asset->getDrive());
            }

            // add history entry and assign case
            if (null !== $case) {
                $history = AssetHistory::fromAsset($asset);
                $asset->setCase($case);
                $asset->setState(State::AssignedCase);
                $asset->setUsage('Aufgrund der Eintragung automatisiert hinzugefügt');
                $asset->setLastUpdatedOn(new \DateTime());

                $this->entityManager->persist($history);
                $this->entityManager->persist($asset);
            }

            // save to database
            $this->entityManager->flush();
            if ($form->get('save')->isClicked()) {
                return $this->redirectToRoute('details_asset', [
                    'id' => $asset->getBarcode(),
                ]);
            }

            $this->addFlash('success', 'asset.add.form.success');
            if ($form->get('save_and_new')->isClicked()) {
                $form = $this->createForm(AddType::class);
            }
        } else {
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        return $this->render('assets/add.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show details page of an asset.
     *
     * @param Request $request Symfony request
     * @param string  $id      DT-ID of object
     */
    #[Route('/objekt/{id}', name: 'details_asset')]
    public function details(string $id, Request $request)
    {
        // query database for object
        $asset = $this->entityManager->getRepository(Asset::class)->find($id);

        // check if object was found
        if (null == $asset) {
            $this->addFlash('danger', 'asset.error.not_found');

            return $this->redirectToRoute('search_assets');
        }

        // render object detail view
        return $this->render('assets/details.html.twig', [
            'id' => $asset->getBarcode(),
            'asset' => $asset,
        ]);
    }

    /**
     * Apply asset action to an asset.
     */
    private function action(
        Request $request,
        AssetActionManager $manager,
        string $id,
        AssetAction $action,
    ) {
        $asset = $this->entityManager->getRepository(Asset::class)->find($id);

        if (null === $asset) {
            $this->addFlash('danger', 'asset.error.not_found');

            return $this->redirectToRoute('search_assets');
        }

        $collection = new ArrayCollection([$asset]);

        // simulate state change to verify that it is legitimate action
        $violations = $manager->isValidAction($collection, $action);

        if (!empty($violations)) {
            foreach ($violations[$asset->getBarcode()] as $error) {
                $this->addFlash('danger', $error->getMessage());
            }

            return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
        }

        // create action form
        $form = $this->createForm(SingleActionType::class, [
            'usage' => $asset->getUsage(),
            'name' => $asset->getName(),
            'note' => $asset->getNote(),
            'drive' => $asset->getDrive(),
        ], [
            'asset_action' => $action,
            'not_before' => $asset->getLastUpdatePerformedOn(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $violations = $manager->performAction($collection, $form->getData(), $action);

            if (empty($violations)) {
                $this->addFlash('success', 'asset.action.success');

                return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
            }

            foreach ($violations[$asset->getBarcode()] as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        // print errors
        foreach ($form->getErrors() as $error) {
            $this->addFlash('danger', $error->getMessage());
        }
        // show additional messages
        foreach ($action->getMessages() as $message) {
            $this->addFlash($message[0], $message[1]);
        }

        return $this->render('assets/single_action.html.twig', [
            'asset' => $asset,
            'action' => $action,
            'form' => $form->createView(),
        ]);
    }

    //
    // ====================== Asset action methods ======================
    //

    /**
     * Show form to edit asset or handle asset edit form request.
     */
    #[Route('/objekt/{id}/editieren', name: 'edit_asset')]
    public function edit(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Edit());
    }

    /**
     * Show form to neutralize drive asset.
     */
    #[Route('/objekt/{id}/neutralisieren', name: 'neutralize_asset')]
    public function neutralize(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Neutralize());
    }

    /**
     * Show clean action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/nullen', name: 'clean_asset')]
    public function cleanAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Clean());
    }

    /**
     * Show destroy action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/vernichtet', name: 'destroy_asset')]
    public function destroyAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Destroy());
    }

    /**
     * Show hand over to person action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/uebergeben', name: 'handover_asset')]
    public function handoverAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Handover());
    }

    /**
     * Show reserve action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/reservieren', name: 'reserve_asset')]
    public function reserveAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Reserve());
    }

    /**
     * Show lost action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/verloren', name: 'lost_asset')]
    public function lostAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Lost());
    }

    /**
     * Show store asset in container action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/einlegen/in/', name: 'store_asset')]
    public function storeAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Store());
    }

    /**
     * Show pull out of container action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/entnehmen', name: 'pull_out_asset')]
    public function pullOutOfContainerAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\PullOutOfContainer());
    }

    /**
     * Show assigning to case action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/in/fall/', name: 'assign_case_asset')]
    public function assignCaseAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\AssignCase());
    }

    /**
     * Show remove case action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/aus/Fall/entfernen', name: 'remove_case_asset')]
    public function removeFromCaseAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\UnassignCase());
    }

    /**
     * Show unbind reservation action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/reservierung/aufheben', name: 'unreserve_asset')]
    public function unbindReservationAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\UnbindReservation());
    }

    /**
     * Show use action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/verwenden', name: 'use_asset')]
    public function useAction(Request $request, AssetActionManager $manager, string $id)
    {
        return $this->action($request, $manager, $id, new Actions\Used());
    }

    /**
     * Show save image on drive action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/Asservatenimage/speichern/', name: 'save_image_on_drive_asset')]
    public function saveImageOnDriveAction(Request $request, AssetActionManager $manager, string $id)
    {
        $asset = $this->entityManager->getRepository(Asset::class)->find($id);

        if (null === $asset) {
            $this->addFlash('danger', 'asset.error.not_found');

            return $this->redirectToRoute('search_assets');
        }

        if ($asset->isHddImageSource()) {
            return $this->action($request, $manager, $id, new Actions\SaveHddImage());
        }
        if (!$asset->isHddImageTarget()) {
            $this->addFlash('danger', 'asset.action.add_image.not_applicable');
            return $this->redirectToRoute('details_asset', ['id' => $id]);
        }
            
        # TODO blöd, not working as a regular action
        return $this->action($request, $manager, $id, new Actions\AddHddImage());


        $repo = $this->entityManager->getRepository(Asset::class);
        $asset = $repo->find($id);

        if (null == $asset) {
            $this->addFlash('danger', 'asset.error.not_found');

            return $this->redirectToRoute('search_assets');
        }

        if (!$asset->isHddImageSource() && !$asset->isHddImageTarget()) {
            $this->addFlash('danger', 'asset.action.add_image.not_applicable');

            return $this->redirectToRoute('details_asset', ['id' => $id]);
        }

        $isSource = $asset->isHddImageSource();
        $source = $isSource ? $asset : null;
        $target = $isSource ? null : $asset;

        $api_url = $isSource ? 'asset_action_query_image_sources' : 'asset_action_query_image_targets';

        // create history entry, but don't persist yet
        $history = $isSource ? AssetHistory::fromAsset($source) : null;

        $options = [
            'confirm' => false,
            'usage_required' => true,
            'not_before' => $isSource ? $source->getLastUpdatePerformedOn() : null,
            'selector' => [
                'name' => 'asset',
                'mapped' => false,
                'class' => Asset::class,
                'label' => fn (Asset $asset) => $asset->getBarcode().' | '.$asset->getName(),
            ],
        ];

        if ($isSource) {
            $options['selector']['choices'] = $repo->findAllHddImageTargetAssets($source, null, 10);
            $options['selector']['model'] = fn ($query, $limit) => $repo->findAllHddImageTargetAssets($source, $query, $limit);
        } else {
            $options['selector']['choices'] = $repo->findAllHddImageSourceAssets($target, null, 10);
            $options['selector']['model'] = fn ($query, $limit) => $repo->findAllHddImageSourceAssets($target, $query, $limit);
        }

        if ($isSource) {
            $source->setState(State::SavedImage);
        }

        $form = $this->createForm(SingleActionType::class, $source, $options);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($isSource) {
                $target = $form->get('asset')->getData();
                if (!$target->isHddImageTarget()) {
                    // todo throw error
                }
            } else {
                // apply form values to source
                $source = $form->get('asset')->getData();
                $history = AssetHistory::fromAsset($source);

                $usage = $form->get('usage')->getData();
                $lastUpdatePerformedOn = $form->get('lastUpdatePerformedOn')->getData();
                $source->setState(State::SavedImage);
                $source->setUsage($usage);
                $source->setLastUpdatePerformedOn($lastUpdatePerformedOn);
                if (!$source->isHddImageSource()) {
                    // todo throw error
                }
            }

            // update source
            $source->setSystemAction(false);
            $source->setModifiedBy($this->getUser());
            $source->setLastUpdatedOn(new \DateTime());

            // add image entry
            $source->addHdd($target);

            $this->entityManager->persist($source);
            $this->entityManager->persist($history);

            $this->entityManager->flush();
            $this->addFlash('success', 'asset.action.add_image.success');

            return $this->redirectToRoute('details_asset', ['id' => $source->getBarcode()]);
        }
        // print errors
        foreach ($form->getErrors() as $error) {
            $this->addFlash('danger', $error->getMessage());
        }

        return $this->render('assets/action.html.twig', [
            'asset' => $asset,
            'options' => [
                'form' => $options,
                'api_url' => $api_url,
            ],
            'cur_state' => $isSource ? $source->getState() : 'asset.action.add_image.save_image_prepare',
            'new_state' => State::SavedImage,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show upload asset picture form.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/upload', name: 'upload_picture_asset')]
    public function uploadPictureAction(string $id, Request $request)
    {
        $asset = $this->entityManager->getRepository(Asset::class)->find($id);

        if (null == $asset) {
            $this->addFlash('danger', 'asset.error.not_found');

            return $this->redirectToRoute('search_assets');
        }

        // if image is set, redirect
        if (null !== $asset->getPicture() || null != $asset->getPicturePath()) {
            return $this->redirectToRoute('details_asset', ['id' => $id]);
        }

        // get all public available images
        $finder = new Finder();
        $finder
            ->in($this->getParameter('pic_directory'))
            ->files()
            ->name(['*.jpg', '*.jpeg']) // TODO why not png?
            ->sortByChangedTime()
        ;
        $public_pictures = iterator_to_array($finder, false);

        $form = $this->createForm(UploadPictureType::class, null, ['public_pictures' => $public_pictures]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $history = AssetHistory::fromAsset($asset);

            // uploaded new picture
            if (!empty($data['picture'])) {
                $image = imagecreatefromjpeg($data['picture']->getRealPath());
                $tempfilename = '';
                $quality = 110;

                do {
                    if ('' != $tempfilename) {
                        unlink($tempfilename);
                    }
                    $quality -= 10;

                    $tempfilename = tempnam(sys_get_temp_dir(), 'uploadpic');
                    imagejpeg($image, $tempfilename, $quality);
                } while (filesize($tempfilename) > (3 * 1024 * 1024) && 10 != $quality); // Imagesize should be under 3MB

                if (10 == $quality) {
                    $this->addFlash('danger', 'asset.upload_pic.error.quality');

                    return $this->render('default/upload_picture.html.twig', [
                        'asset' => $asset,
                        'form' => $form->createView(),
                    ]);
                }

                $picture = new \Symfony\Component\HttpFoundation\File\File($tempfilename, true);

                if ($data['is_public']) {
                    $filename = md5(uniqid()).'.'.$picture->guessExtension();
                    $picture->move($this->getParameter('pic_directory'), $filename);
                    $asset->setPicturePath($filename);
                    $asset->setUsage($this->translator->trans('asset.upload_pic.usage_public'));
                } else {
                    $asset->setPicture($picture);
                    $asset->setUsage($this->translator->trans('asset.upload_pic.usage_private'));
                }

                // clear remaining picture in temp folder
                if ('' != $tempfilename && file_exists($tempfilename)) {
                    unlink($tempfilename);
                }
            } elseif (!empty($data['select_public'])) {
                $asset->setPicturePath($data['select_public']->getRelativePathname());
                $asset->setUsage($this->translator->trans('asset.upload_pic.usage_selected'));
            } else {
                $this->addFlash('danger', 'asset.upload_pic.error.not_saved');

                return $this->render('default/upload_picture.html.twig', [
                    'asset' => $asset,
                    'form' => $form->createView(),
                ]);
            }

            $this->entityManager->persist($history);
            $this->entityManager->persist($asset);
            $this->entityManager->flush();

            $this->addFlash('success', 'asset.upload_pic.success');

            return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
        }
        // print errors
        foreach ($form->getErrors() as $error) {
            $this->addFlash('danger', $error->getMessage());
        }

        $this->addFlash('info', 'asset.upload_pic.info');

        return $this->render('default/upload_picture.html.twig', [
            'asset' => $asset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays FAQ and help page for extended asset search.
     * @codeCoverageIgnore
     */
    #[Route('/objekte/faq', name: 'eas_faq')]
    public function searchFaq()
    {
        return $this->render('eas/faq.html.twig');
    }

    //
    // ====================== JS API methods ======================
    //

    /**
     * Helper function to list available cases to link to asset when adding new assets.
     * The request parameter `query` can optionally be used to filter results.
     *
     * @see add()
     *
     * @api
     */
    #[Route('/asset/cases', name: 'add_asset_query_cases')]
    public function listCaseOptions(Request $request, ExtendedCaseSearch $extendedSearch): JsonResponse
    {
        $search = \trim($request->query->get('query', ''));
        $limit = $request->query->get('limit', 10);

        $limit = match (\intval($limit)) {
            10 => 10,
            25 => 25,
            50 => 50,
            default => 10,
        };

        $builder = $extendedSearch->generateSearchQuery($search);
        $builder->andWhere('caseFile.active = 1');
        $builder->orderBy('caseFile.openedOn', 'DESC');

        $query = $builder->getQuery();
        $total = $builder
        ->select('COUNT(caseFile)')
        ->getQuery()
        ->getSingleScalarResult();

        $query->setMaxResults($limit);
        $cases = $query->execute();

        $data = [];
        foreach ($cases as $case) {
            $data[] = [
                'id' => $case->getId(),
                'caseId' => $case->getCaseId(),
                'description' => $case->getDescription(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
            'total' => $total,
        ]);
    }

    /**
     * Helper function to list available storage locations when storing asset.
     * The request parameter `query` can optionally be used to filter results.
     *
     * @see storeAction()
     *
     * @api
     */
    #[Route('/asset/locations', name: 'asset_action_query_locations')]
    public function listStorageOptions(
        Request $request,
        ExtendedAssetSearch $extendedSearch,
        TranslatorInterface $translator,
    ): JsonResponse {
        $search = \trim($request->query->get('query', ''));
        $limit = $request->query->get('limit', 10);

        $limit = match (\intval($limit)) {
            10 => 10,
            25 => 25,
            50 => 50,
            default => 10,
        };

        $builder = $extendedSearch->generateSearchQuery($search);
        /**
         * @var AssetRepository
         */
        $repo = $this->entityManager->getRepository(Asset::class);
        $builder->andWhere($repo->isEditableQuery('asset'));
        $builder->andWhere($repo->isStorageQuery('asset'));
        $builder->orderBy('asset.lastUpdatedOn', 'DESC');

        $query = $builder->getQuery();
        $total = $builder
        ->select('COUNT(asset)')
        ->getQuery()
        ->getSingleScalarResult();

        $query->setMaxResults($limit);
        $assets = $query->execute();

        $data = [];
        foreach ($assets as $asset) {
            $data[] = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->trans($translator),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
            'total' => $total,
        ]);
    }

    /**
     * Helper function to list available targets for a hdd image.
     *
     * @see saveImageOnDriveAction()
     *
     * @api
     */
    #[Route('/asset/image_targets', name: 'asset_action_query_image_targets')]
    public function listHddImageTargets(
        Request $request,
        ExtendedAssetSearch $extendedSearch,
        TranslatorInterface $translator,
    ): JsonResponse {
        $search = \trim($request->query->get('query', ''));
        $limit = $request->query->get('limit', 10);

        $limit = match (\intval($limit)) {
            10 => 10,
            25 => 25,
            50 => 50,
            default => 10,
        };


        $builder = $extendedSearch->generateSearchQuery($search);
        /**
         * @var AssetRepository
         */
        $repo = $this->entityManager->getRepository(Asset::class);
        $builder->andWhere($repo->isEditableQuery('asset'));
        $builder->andWhere($repo->isHddImageTargetQuery('asset'));

        $query = $builder->getQuery();
        $total = $builder
        ->select('COUNT(asset)')
        ->getQuery()
        ->getSingleScalarResult();

        $query->setMaxResults($limit);
        $assets = $query->execute();

        $data = [];
        foreach ($assets as $asset) {
            $data[] = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->trans($translator),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
            'total' => $total,
        ]);
    }

    /**
     * Helper function to list available sources for a hdd image.
     *
     * @see saveImageOnDriveAction()
     *
     * @api
     */
    #[Route('/asset/image_sources', name: 'asset_action_query_image_sources')]
    public function listHddImageSources(
        Request $request,
        ExtendedAssetSearch $extendedSearch,
        TranslatorInterface $translator,
    ): JsonResponse {
        $search = \trim($request->query->get('query', ''));
        $limit = $request->query->get('limit', 10);

         $limit = match (\intval($limit)) {
            10 => 10,
            25 => 25,
            50 => 50,
            default => 10,
        };

        $builder = $extendedSearch->generateSearchQuery($search);
        /**
         * @var AssetRepository
         */
        $repo = $this->entityManager->getRepository(Asset::class);
        $builder->andWhere($repo->isEditableQuery('asset'));
        $builder->andWhere($repo->isHddImageSourceQuery('asset'));

        $query = $builder->getQuery();
        $total = $builder
        ->select('COUNT(asset)')
        ->getQuery()
        ->getSingleScalarResult();

        $query->setMaxResults($limit);
        $assets = $query->execute();

        $data = [];
        foreach ($assets as $asset) {
            $data[] = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->trans($translator),
                'color' => $asset->getCategory()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
            'total' => $total,
        ]);
    }


    /**
     * Search for assets.
     *
     * @api
     */
    #[Route('/asset/assets', name: 'assets')]
    public function getAssets(
        Request $request,
        ExtendedAssetSearch $extendedSearch,
    ): JsonResponse {
        $search = \trim($request->query->get('query', ''));
        $limit = $request->query->get('limit', 10);
         $limit = match (\intval($limit)) {
            10 => 10,
            25 => 25,
            50 => 50,
            default => 10,
        };

        $builder = $extendedSearch->generateSearchQuery($search);
        $query = $builder->getQuery();
        $total = $builder
        ->select('COUNT(asset)')
        ->getQuery()
        ->getSingleScalarResult();

        $query->setMaxResults($limit);
        $assets = $query->execute();

        $data = [];
        foreach ($assets as $asset) {
            $data[] = [
                'active' => $asset->isEditable(),
                'barcode' => $asset->getBarcode(),
                'category' => $asset->getCategory()->trans($this->translator),
                'categoryColor' => $asset->getCategory()->bootstrapColor(),
                'state' => $asset->getState()->trans($this->translator),
                'stateColor' => $asset->getState()->bootstrapColor(),
                'name' => $asset->getName(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
            'total' => $total,
        ]);
    }
}
