<?php

namespace App\Form\Asset;

use App\Action\Asset\ActionInterface;
use App\Action\Asset\Edit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Form for applying asset action.
 *
 * @author Ben Brooksnieder
 */
class SingleActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /**
         * @var ActionInterface
         */
        $action = $options['asset_action'];
        $notBefore = $options['not_before'];

        $builder
            // usage field to describe action
            ->add('usage', Field\TextareaType::class, [
                'label' => $action->isUsageRequired() ?
                    'asset.action.form.usage.required' :
                    'asset.action.form.usage.optional',
                'required' => $action->isUsageRequired(),
                'constraints' => $action->isUsageRequired() ? new Constraints\NotBlank() : [],
            ])
            //  action due date
            ->add('lastUpdatePerformedOn', Field\DateTimeType::class, [
                'label' => 'asset.action.form.due_date',
                'required' => true,
                'data' => new \DateTime(),
                'widget' => 'single_text',
                'with_seconds' => true,
                'constraints' => $notBefore ? new Constraints\GreaterThanOrEqual($notBefore) : [],
            ])
            ->add('save', Field\SubmitType::class, [
                'label' => 'asset.action.form.save',
                'attr' => ['class' => 'btn-primary'],
            ])
        ;

        // add action specific fields
        $action->buildForm($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'asset_action' => null,
            'not_before' => null,
        ]);

        $resolver->setAllowedTypes('asset_action', ActionInterface::class);
        $resolver->setAllowedTypes('not_before', ['null', \DateTimeInterface::class]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['edit_action_class'] = Edit::class;
    }
}
