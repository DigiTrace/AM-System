<?php

namespace App\Controller;

use App\Entity\Asset;
use App\Entity\AssetHistory;
use App\Entity\CaseFile;
use App\Enum\AssetCategory as Category;
use App\Enum\AssetState as State;
use App\Form\ActionAssetType;
use App\Form\AddAssetType;
use App\Form\EditAssetType;
use App\Form\SimpleAssetSearchType;
use App\Form\UploadPictureType;
use App\Service\ExtendedAssetSearch;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Controller for managing assets.
 *
 * @todo Test
 * @todo Add:
 * - [x] add
 * - [x] details
 * - [x] edit
 * - [ ] Alter multiple
 * - [x] Null action
 * - [x] Use action
 * - [x] Destroy action
 * - [x] Lost action
 * - [x] Handover action
 * - [x] Reserve action
 * - [x] Unreserve action
 * - [x] Pull out action
 * - [x] Remove from case action
 * - [x] Neutralize action
 * - [x] Store action
 * - [x] Add to case action
 * - [x] Upload picture
 * - [x] Select exhibit hdd
 * - [x] Save image action
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

        $form = $this->createForm(SimpleAssetSearchType::class, null, [
            'method' => 'GET',
            'csrf_protection' => false,
            'attr' => ['class' => 'navbar-form navbar-right', 'id' => 'search_form'],
            'search_action' => $this->generateURL('search_assets'),
            'limit' => $session->get('limit'),
        ]);
        $form->handleRequest($request);

        // if form is submitted, apply form parameters
        if ($form->isSubmitted() && $form->isValid()) {
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
        $search ??= $request->get('suche');
        $search ??= $request->get('search');

        // apply extended asset search to create query
        if ($search) {
            $query = $extendedAssetSearch->generateSearchQuery($search);
            foreach ($extendedAssetSearch->getErrors() as $err) {
                $this->addFlash($err['type'], $err['message']);
            }
        }

        // no search query or parse error, apply default asset listing
        if ($query === null) {
            $repo = $this->entityManager->getRepository(Asset::class);
            $query = $repo->createQueryBuilder('asset')->getQuery();

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

        // render object table
        return $this->render('assets/search.html.twig', [
            'form' => $form->createView(),
            'eas_categories' => Category::cases(),
            'eas_states' => State::cases(),
            'pagination' => $pagination,
            'regex_single_match' => $extendedAssetSearch::$regex_single_match,
            'regex_multiple_match' => $extendedAssetSearch::$regex_multiple_match,
        ]);
    }

    // public function editAssets(Request $request, SessionInterface $session) {
    //     $form = $this->createForm(BatchAssetActionType::class, null, []);
    //     $form->handleRequest($request);

    //     if ($form->isSubmitted() && $form->isValid()) {
    //         $data = $form->getData();
    //         if ($data)
    //     }

    //     if (    $chooseform->isSubmitted() && 
    //             $chooseform->isValid() ) {
            
    //         $temp = $chooseform->getData();
            
    //         // if a Objects has to be stored or added to case, this action cant
    //         // proceed, if contextthing isnt set
    //         if(($temp["newstatus"] == State::AssignedCase ||
    //             $temp["newstatus"] == State::StoredInContainer) &&
    //             $temp["contextthings"] == null){
                
    //             $this->addFlash('danger','selected_action_needs_contextthings');
    //         }
    //         else{   
    //             $session->set("newstatus",$temp["newstatus"]);
    //             $session->set("newdescription",$temp["newdescription"]);
    //             $session->set("contextthings",$temp["contextthings"]);
    //             $session->set("dueDate",$temp["dueDate"]);
                
    //             return $this->redirectToRoute('alter_multiple_objects');
    //         }
            
    //     }
    //     else{
    //         $this->addFlash("info",'action_description_mass_update_part1');
    //     }
    //     return $this->render('default/select_action.html.twig', array(
    //         'chooseform'=> $chooseform->createView(),
    //     ));
    // }

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
        $form = $this->createForm(AddAssetType::class, null, []);
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
                $form = $this->createForm(AddAssetType::class);
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
     * Show form to edit asset or handle asset edit form request.
     */
    #[Route('/objekt/{id}/editieren', name: 'edit_asset')]
    public function edit(string $id, Request $request)
    {
        // query database for asset
        $asset = $this->entityManager->getRepository(Asset::class)->find($id);

        // check if asset was found
        if (null == $asset) {
            $this->addFlash('danger', 'asset.error.not_found');

            return $this->redirectToRoute('search_assets');
        }

        // create history entry, but don't persist yet
        $history = AssetHistory::fromAsset($asset);
        $drive = $asset->getDrive();

        // simulate state change to verify that it is legitimate action
        $currentState = $asset->getState();
        $asset->setState(State::Edited);

        // proccess form
        $form = $this->createForm(EditAssetType::class, $asset, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // restore state for change calculation
            $asset->setState($currentState);
            // check if entity has changed
            $uow = $this->entityManager->getUnitOfWork();
            $uow->computeChangeSets();
            $asset_changes = $uow->getEntityChangeSet($asset);
            $drive_changes = $asset->isDrive() ? $uow->getEntityChangeSet($drive) : [];
            
            // changes are detected, refresh entities, reapply changes and commit
            if (empty($drive_changes) && empty($asset_changes)) {
                $this->addFlash('info', 'asset.edit.no_changes_made');
            }
            else {       
                $propertyAccesor = PropertyAccess::createPropertyAccessor();
                if ($drive_changes) {
                    $uow->refresh($drive);
                    foreach ($drive_changes as $key => $value) {
                        $propertyAccesor->setValue($drive, $key, $value['1']);
                    }
                    $this->entityManager->persist($drive);
                }
                
                $uow->refresh($asset);
                foreach ($asset_changes as $key => $value) {
                    $propertyAccesor->setValue($asset, $key, $value['1']);
                }
                
                $asset->setState(State::Edited);
                $asset->setSystemAction(false);
                $asset->setModifiedBy($this->getUser());
                $asset->setLastUpdatedOn(new \DateTime());
                $this->entityManager->persist($asset);
                $this->entityManager->persist($history);

                $this->entityManager->flush();
                $this->addFlash('success', 'asset.edit.success');
                
                return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);        
            }
        } else {
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
            $this->addFlash('info', 'asset.edit.info');
        }

        return $this->render('assets/edit.html.twig', [
            'asset' => $asset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show form to neutralize drive asset.
     */
    #[Route('/objekt/{id}/neutralisieren', name: 'neutralize_asset')]
    public function neutralize(string $id, Request $request)
    {
        // query database for asset
        $asset = $this->entityManager->getRepository(Asset::class)->find($id);

        // check if asset was found
        if (null == $asset) {
            $this->addFlash('danger', 'asset.error.not_found');

            return $this->redirectToRoute('search_assets');
        }

        // action only allowed for drives
        if (Category::Hdd != $asset->getCategory()) {
            $this->addFlash('danger', 'asset.action.neutralize.not_hdd');

            return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
        }

        // create history entry, but don't persist yet
        $history = AssetHistory::fromAsset($asset);
        $currentState = $asset->getState();

        // bypass same state validation and check if neutralize is applicable
        if (State::Cleaned != $currentState) {
            $asset->setState(State::Cleaned);
        } elseif (null !== $asset->getLocation()) {
            $asset->setState(State::StoredInContainer);
        } elseif (null !== $asset->getCase()) {
            $asset->setState(State::AssignedCase);
        } else {
            $this->addFlash('danger', 'asset.action.neutralize.already_neutralized');

            return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
        }

        // proccess form
        $form = $this->createForm(ActionAssetType::class, $asset, []);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $asset->setSystemAction(true);
            $asset->setModifiedBy($this->getUser());
            $asset->setLastUpdatedOn(new \DateTime());

            // TODO pack all changes into one history entry

            // if state is not cleaned, set to cleaned
            if (State::Cleaned != $currentState) {
                $this->entityManager->persist($history);
                $history = AssetHistory::fromAsset($asset);
            }

            // if location is not null, remove from location
            if (null !== $asset->getLocation()) {
                $this->entityManager->persist($history);
                $asset->setLocation(null);
                $history = AssetHistory::fromAsset($asset);
            }

            // if assigned to case, remove from case
            if (null !== $asset->getCase()) {
                $this->entityManager->persist($history);
                $asset->setCase(null);
            }

            // save asset changes
            $this->entityManager->persist($asset);

            $this->entityManager->flush();
            $this->addFlash('success', 'asset.action.neutralize.success');

            return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
        } else {
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
            $this->addFlash('info', 'asset.action.neutralize.info');
        }

        return $this->render('assets/action.html.twig', [
            'asset' => $asset,
            'cur_state' => $currentState,
            'new_state' => 'asset.action.neutralize.state',
            'form' => $form->createView(),
        ]);
    }

    /**
     * Show clean action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/nullen', name: 'clean_asset')]
    public function cleanAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => false,
            ],
            'beforePersist' => fn (Asset $asset) => $asset->flushImages(),
        ];

        return $this->action($id, State::Cleaned, $options, $request);
    }

    /**
     * Show destroy action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/vernichtet', name: 'destroy_asset')]
    public function destroyAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => true,
                'usage_required' => true,
            ],
            'messages' => [
                ['warning', 'asset.action.destroy.warning'],
            ],
        ];

        return $this->action($id, State::Destroyed, $options, $request);
    }

    /**
     * Show hand over to person action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/uebergeben', name: 'handover_asset')]
    public function handoverAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => true,
            ],
            'messages' => [
                ['info', 'asset.action.handover.info'],
            ],
        ];

        return $this->action($id, State::HandoverPerson, $options, $request);
    }

    /**
     * Show reserve action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/reservieren', name: 'reserve_asset')]
    public function reserveAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => false,
            ],
            'beforePersist' => fn (Asset $asset) => $asset->setReservedBy($this->getUser()),
        ];

        return $this->action($id, State::Reserved, $options, $request);
    }

    /**
     * Show lost action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/verloren', name: 'lost_asset')]
    public function lostAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => true,
                'usage_required' => false,
            ],
            'messages' => [ 
                ['info', 'asset.action.lost.info'],
                ['warning', 'asset.action.lost.warning'],
            ],
        ];

        return $this->action($id, State::Lost, $options, $request);
    }

    /**
     * Show store asset in container action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/einlegen/in/', name: 'store_asset')]
    public function storeAction(string $id, Request $request)
    {
        $repo = $this->entityManager->getRepository(Asset::class);

        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => true,
                'selector' => [
                    'name' => 'location',
                    'class' => Asset::class,
                    'label' => fn (Asset $asset) => $asset->getBarcode().' | '.$asset->getName(),
                    'choices' => $repo->findAllStorageAssets(null, 10),
                    'model' => fn ($query, $limit) => $repo->findAllStorageAssets($query, $limit),
                ],
            ],
            'api_url' => 'asset_action_query_locations',
        ];

        return $this->action($id, State::StoredInContainer, $options, $request);
    }

    /**
     * Show pull out of container action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/entnehmen', name: 'pull_out_asset')]
    public function pullOutOfContainerAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => true,
            ],
            'beforePersist' => fn (Asset $asset) => $asset->setLocation(null),
            'messages' => [ 
                ['info', 'asset.action.remove_container.info'],
            ],
        ];

        return $this->action($id, State::PulledOutOfContainer, $options, $request);
    }

    /**
     * Show assigning to case action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/in/fall/', name: 'assign_case_asset')]
    public function assignCaseAction(string $id, Request $request)
    {
        $repo = $this->entityManager->getRepository(CaseFile::class);

        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => true,
                'selector' => [
                    'name' => 'case',
                    'class' => CaseFile::class,
                    'label' => fn (CaseFile $case) => $case->getCaseId().' | '.$case->getDescription(),
                    'choices' => $repo->findBySimpleSearch('%%', 10),
                    'model' => fn ($query, $limit) => $repo->findBySimpleSearch($query, $limit),
                ],
            ],
            'api_url' => 'add_asset_query_cases',
        ];

        return $this->action($id, State::AssignedCase, $options, $request);
    }

    /**
     * Show remove case action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/aus/Fall/entfernen', name: 'remove_case_asset')]
    public function removeFromCaseAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => true,
            ],
            'beforePersist' => fn (Asset $asset) => $asset->setCase(null),
            'messages' => [
                ['info', 'asset.action.unassing_case.info'],
            ],
        ];

        return $this->action($id, State::RemovedFromCase, $options, $request);
    }

    /**
     * Show unbind reservation action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/reservierung/aufheben', name: 'unreserve_asset')]
    public function unbindReservationAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => false,
            ],
            'beforePersist' => fn (Asset $asset) => $asset->setReservedBy(null),
        ];

        return $this->action($id, State::UnbindReservation, $options, $request);
    }

    /**
     * Show use action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/verwenden', name: 'use_asset')]
    public function useAction(string $id, Request $request)
    {
        $options = [
            'form' => [
                'confirm' => false,
                'usage_required' => true,
            ],
        ];

        return $this->action($id, State::Used, $options, $request);
    }

    /**
     * Show save image on drive action.
     *
     * @see action()
     */
    #[Route('/objekt/{id}/Asservatenimage/speichern/', name: 'save_image_on_drive_asset')]
    public function saveImageOnDriveAction(string $id, Request $request)
    {
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

        $form = $this->createForm(ActionAssetType::class, $source, $options);
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
        } else {
            // print errors
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
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
     * Show form or apply asset action.
     *
     * @param Asset|string $asset   asset instance or DT-barcode
     * @param State        $state   new state applied in this action
     * @param array        $options options for customization
     */
    private function action(
        Asset|string $asset, State $state, array $options, Request $request)
    {
        if (\is_string($asset)) {
            $asset = $this->entityManager->getRepository(Asset::class)->find($asset);
        }

        if (null == $asset) {
            $this->addFlash('danger', 'asset.error.not_found');

            return $this->redirectToRoute('search_assets');
        }

        // create history entry, but don't persist yet
        $history = AssetHistory::fromAsset($asset);

        // simulate state change to verify that it is legitimate action
        $currentState = $asset->getState();
        $asset->setState($state);

        $violations = $this->validator->validate($asset);
        if ($violations->count() > 0) {
            foreach ($violations as $error) {
                $this->addFlash('danger', $error->getMessage());
            }

            return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
        }

        $options['form'] ??= [];
        $options['form']['not_before'] = $asset->getLastUpdatePerformedOn();

        $form = $this->createForm(ActionAssetType::class, $asset, $options['form']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $asset->setSystemAction(false);
            $asset->setModifiedBy($this->getUser());
            $asset->setLastUpdatedOn(new \DateTime());

            // apply callback
            if (!empty($options['beforePersist']) && \is_callable($options['beforePersist'])) {
                $options['beforePersist']($asset);
            }

            $this->entityManager->persist($asset);
            $this->entityManager->persist($history);

            if ($asset->isDrive()) {
                $this->entityManager->persist($asset->getDrive());
            }

            $this->entityManager->flush();
            $this->addFlash('success', 'asset.action.success');

            return $this->redirectToRoute('details_asset', ['id' => $asset->getBarcode()]);
        } else {
            // print errors
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        // show additional messages
        foreach ($options['messages'] ?? [] as $message) {
            $this->addFlash($message[0], $message[1]);
        }

        return $this->render('assets/action.html.twig', [
            'asset' => $asset,
            'options' => $options,
            'cur_state' => $currentState,
            'new_state' => $state,
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
                $asset->setUsage($this->translator->trans('asset.upload_pic.usage_selcted'));
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
        } else {
            // print errors
            foreach ($form->getErrors() as $error) {
                $this->addFlash('danger', $error->getMessage());
            }
        }

        $this->addFlash('info', 'asset.upload_pic.info');

        return $this->render('default/upload_picture.html.twig', [
            'asset' => $asset,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Displays FAQ and help page for extended asset search.
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
    public function listCaseOptions(Request $request): JsonResponse
    {
        $query = $request->get('query', '');

        if (empty(\trim($query))) {
            $query = null;
        }

        $repository = $this->entityManager->getRepository(CaseFile::class);
        $cases = $repository->findBySimpleSearch($query, 10);

        $data = [];
        foreach ($cases as $case) {
            $data[] = [
                'val' => $case->getId(),
                'text' => $case->getCaseId().' | '.$case->getDescription(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
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
    public function listStorageOptions(Request $request): JsonResponse
    {
        $query = $request->get('query', '');

        if (empty(\trim($query))) {
            $query = null;
        }

        $repository = $this->entityManager->getRepository(Asset::class);
        $locations = $repository->findAllStorageAssets($query, 10);

        $data = [];
        foreach ($locations as $asset) {
            $data[] = [
                'val' => $asset->getBarcode(),
                'text' => $asset->getBarcode().' | '.$asset->getName(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
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
    public function listHddImageTargets(Request $request): JsonResponse
    {
        $query = $request->get('query', '');

        if (empty(\trim($query))) {
            $query = null;
        }

        $repository = $this->entityManager->getRepository(Asset::class);
        $locations = $repository->findAllHddImageTargetAssets(null, $query, 10);

        $data = [];
        foreach ($locations as $asset) {
            $data[] = [
                'val' => $asset->getBarcode(),
                'text' => $asset->getBarcode().' | '.$asset->getName(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
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
    public function listHddImageSources(Request $request): JsonResponse
    {
        $query = $request->get('query', '');

        if (empty(\trim($query))) {
            $query = null;
        }

        $repository = $this->entityManager->getRepository(Asset::class);
        $locations = $repository->findAllHddImageSourceAssets(null, $query, 10);

        $data = [];
        foreach ($locations as $asset) {
            $data[] = [
                'val' => $asset->getBarcode(),
                'text' => $asset->getBarcode().' | '.$asset->getName(),
            ];
        }

        return new JsonResponse([
            'update' => true,
            'data' => $data,
        ]);
    }
}
