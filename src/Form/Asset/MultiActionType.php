<?php

namespace App\Form\Asset;

use App\Action\Asset as Actions;
use App\Action\Asset\AssetAction;
use App\Entity\Asset;
use App\Repository\AssetRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Form used for multiple selection of assets in asset overview.
 *
 * @author Ben Brooksnieder
 */
class MultiActionType extends AbstractType implements DataTransformerInterface
{
    private static ?array $actions = null;

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Allowed asset actions for multiple edits.
     *
     * @return array<AssetAction>
     */
    public static function getActions(): array
    {
        if (!static::$actions) {
            static::$actions = [
                new Actions\Clean(),
                new Actions\Customer(),
                new Actions\Destroy(),
                new Actions\Handover(),
                new Actions\Reserve(),
                new Actions\Lost(),
                new Actions\Store(),
                new Actions\PullOutOfContainer(),
                new Actions\AssignCase(),
                new Actions\UnassignCase(),
                new Actions\UnbindReservation(),
                new Actions\Used(),
                new Actions\Neutralize(),
            ];
        }

        return static::$actions;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('preview', Field\SubmitType::class, [
            'label' => 'form.apply',
            'validation_groups' => false,
            'attr' => ['disabled' => true, 'class' => 'btn-primary'],
        ]);

        $builder->add('action', ChoiceType::class, [
            'label' => 'asset.action.form.action',
            'choices' => static::getActions(),
            'data' => new Actions\Clean(),
            'choice_label' => fn (AssetAction $choice, string $key, mixed $value) => $choice->getName(),
            'required' => true,
        ]);

        $builder->add('assets', Field\CollectionType::class, [
            'entry_type' => Field\TextType::class,
            'entry_options' => [
                'validation_groups' => [false],
            ],
            'allow_add' => true,
            'constraints' => new Constraints\NotBlank(),
        ]);

        $builder->add('usage', Field\TextareaType::class, [
            'label' => 'asset.action.form.usage.required',
            'required' => true,
            'constraints' => new Constraints\NotBlank(),
        ]);

        //  action due date
        $notBefore = $options['not_before'];
        $builder->add('lastUpdatePerformedOn', Field\DateTimeType::class, [
            'label' => 'asset.action.form.due_date',
            'required' => true,
            'data' => new \DateTime(),
            'widget' => 'single_text',
            'with_seconds' => true,
            'constraints' => $notBefore ? new Constraints\GreaterThanOrEqual($notBefore) : [],
        ]);

        $builder->add('save', Field\SubmitType::class, [
            'label' => $options['preview'] ? 'form.apply' : 'asset.action.form.save',
            'attr' => ['class' => 'btn-primary'],
        ]);

        // apply all action depended form elements
        foreach (static::getActions() as $action) {
            $action->buildForm($builder, $options);
        }

        $builder->get('assets')->addModelTransformer($this);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'preview' => false,
            'not_before' => null,
        ]);

        $resolver->addAllowedTypes('preview', 'bool');
        $resolver->setAllowedTypes('not_before', ['null', \DateTimeInterface::class]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['store_action_class'] = Actions\Store::class;
        $view->vars['assign_case_action_class'] = Actions\AssignCase::class;
        $view->vars['store_action_value'] = array_search(new Actions\Store(), static::getActions());
        $view->vars['assign_case_action_value'] = array_search(new Actions\AssignCase(), static::getActions());
    }

    /**
     * Transforms a list of assets to an array of barcodes.
     *
     * @param Asset|null $case
     */
    public function transform($case): mixed
    {
        if (null === $case) {
            return [];
        }

        return [];
    }

    /**
     * Transforms a array of barcodes to assets.
     *
     * @param array $data
     *
     * @throws TransformationFailedException if no assets are found
     */
    public function reverseTransform($data): ?Collection
    {
        if (empty($data)) {
            return null;
        }

        /**
         * @var AssetRepository
         */
        $repo = $this->entityManager->getRepository(Asset::class);

        $builder = $repo->createQueryBuilder('a');
        $builder->where((new \Doctrine\ORM\Query\Expr())->in('a.barcode', ':barcodes'))
        ->setParameter('barcodes', $data);

        $assets = $builder->getQuery()->getResult();

        if (null === $assets) {
            // causes a validation error
            // this message is not shown to the user
            // see the invalid_message option
            throw new TransformationFailedException(\sprintf('No matching assets found'));
        }

        return new ArrayCollection($assets);
    }
}
