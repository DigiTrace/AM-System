<?php

namespace App\Form;

use App\Entity\Asset;
use App\Entity\Fall;
use App\Enum\AssetCategory as Category;
use App\Validator\Barcode;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Container\Container;
use ReflectionProperty;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * @author Ben Brooksnieder
 */
class AddAssetType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('barcode', Field\TextType::class, [
                'label' => 'asset.add.form.barcode',
                'attr' => [
                    'autofocus' => true,
                    'placeholder' => 'DTXX00000',
                ],
                'constraints' => new Barcode([
                    'payload' => ['propertyPath' => 'parent.data.category'],
                ]),
                'required' => true,
            ])
            ->add('name', Field\TextType::class, [
                'label' => 'asset.add.form.name',
                'constraints' => new Constraints\NotBlank(),
                'required' => true,
            ])
            ->add('category', Field\EnumType::class, [
                'class' => Category::class,
                'label' => 'asset.add.form.category',
                // return data for JS
                'choice_attr' => array_map(function($category) {
                        return [
                            'data-prefix' => $category->getDtBarcodePrefix(),
                            'data-drive' => intval($category->isDrive()),
                            'data-storage' => intval($category->isStorage()),
                        ];
                    }, Category::cases()),
                'attr' => [
                    'size' => '6',
                ],
                'constraints' => new Constraints\NotBlank(),
                'required' => true,
            ])
            ->add('usage', Field\TextareaType::class, [
                'label' => 'asset.add.form.usage',
                'required' => false,
            ])
            ->add('note', Field\TextareaType::class, [
                'label' => 'asset.add.form.note',
                'required' => false,
            ])
            ->add('storageOverride', Field\ChoiceType::class, [
                'label' => 'asset.add.form.storage_override',
                'help' => 'asset.add.form.help.storage_override',
                'data' => null,
                'choices' => [
                    'asset.add.form.storage_override_opt.auto' => null,
                    'asset.add.form.storage_override_opt.enabled' => true,
                    'asset.add.form.storage_override_opt.disabled' => false,
                ],
                'choice_translation_domain' => true,
                'required' => true,
            ])
            ->add('lastUpdatePerformedOn', Field\DateTimeType::class, [
                'label' => 'asset.add.form.due_date',
                'help' => 'asset.add.form.help.due_date',
                'widget' => 'single_text',
                'data' => new \DateTime(),
                'attr' => ['placeholder' => 'dd.MM.yyyy HH:mm'],
            ])
            ->add('case_search', Field\SearchType::class, [
                'label' => 'asset.add.form.case_search',
                'help' => 'asset.add.form.help.case_search',
                'mapped' => false,
                'required' => false,
            ])
            ->add('case', EntityType::class, [
                'label' => 'asset.add.form.case',
                'placeholder' => 'asset.add.form.no_case',
                'class' => Fall::class,
                'choice_label' => fn (Fall $case) => $case->getCaseId().' | '.$case->getBeschreibung(),
                'choices' => [],
                'attr' => ['size' => '7'],
                'required' => false,
            ])
            // add drive form
            ->add('drive', DriveType::class)
            ->add('save', Field\SubmitType::class, [
                'label' => 'asset.add.form.save',
            ])
            ->add('save_and_new', Field\SubmitType::class, [
                'label' => 'asset.add.form.save_and_new',
            ])
            ->add('save_and_keep', Field\SubmitType::class, [
                'label' => 'asset.add.form.save_and_keep',
            ]);

        // add listener to remove drive depending on category
        $builder->addEventListener(FormEvents::SUBMIT, function (SubmitEvent $event): void {
            $data = $event->getData();
            $rp = new ReflectionProperty(Asset::class, 'category');

            // remove drive attr if asset is not drive
            if ($rp->isInitialized($data) && !$data->isDrive()) {
                $data->setDrive(null);
            }
        });

        // add custom submit handler for "case search", in order to populate case choices
        $builder->get('case_search')->addEventListener(FormEvents::POST_SUBMIT, function (PostSubmitEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            $repository = $this->entityManager->getRepository(Fall::class);
            $cases = $repository->findBySimpleSearch($data, 6);

            $form->getParent()->add('case', EntityType::class, [
                'label' => 'asset.add.form.case',
                'class' => Fall::class,
                'choice_label' => fn (Fall $case) => $case->getCaseId().' | '.$case->getBeschreibung(),
                'choices' => $cases,
                'placeholder' => 'asset.add.form.no_case',
                'attr' => ['size' => '7'],
                'required' => false,
            ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
            'constraints' => [
                new UniqueEntity(fields: ['barcode'], message: 'asset.add.form.error.duplicate'),
            ],
        ]);
    }
}
