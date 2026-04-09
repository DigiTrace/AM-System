<?php

namespace App\Form;

use App\Entity\Asset;
use App\Entity\CaseFile;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Constraints;

/**
 * Form for applying asset action.
 * 
 * @author Ben Brooksnieder
 */
class ActionAssetType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $usageRequired = $options['usage_required'];
        $notBefore = $options['not_before'];

        $builder
            // usage field to describe action
            ->add('usage',  Field\TextareaType::class,[
                'label'       => $usageRequired ? 'asset.action.form.usage.required' : 'asset.action.form.usage.optional',
                'required'    => $usageRequired,
                'constraints' => $usageRequired ? new Constraints\NotBlank : [],
            ])
            //  action due date 
            ->add('lastUpdatePerformedOn', Field\DateTimeType::class,[
                'label'        => 'asset.action.form.due_date',
                'required'     => true,
                'data'         => new \Datetime(),
                'widget'       => 'single_text',
                'with_seconds' => true,
                'constraints'  => $notBefore ? new Constraints\GreaterThanOrEqual($notBefore) : [],
            ])
            ->add('save', Field\SubmitType::class,[
                'label' => 'asset.action.form.save',
            ])
        ;

        // allow list selection
        if (!empty($options['selector'])) {
            $selector = $options['selector'];

            $builder
                ->add('selector_search', Field\SearchType::class, [
                    'label' => "asset.action.form.{$selector['name']}_search",
                    'help' => "asset.action.form.help.{$selector['name']}_search",
                    'mapped' => false,
                    'required' => false,
                ])
                // ->add($selector['name'], EntityType::class, [
                //     'label' => "asset.action.form.{$selector['name']}",
                //     'mapped' => $selector['mapped'] ?? true,
                //     'class' => $selector['class'],
                //     'choice_label' => $selector['label'],
                //     'choices' => $selector['choices'],
                //     'attr' => ['size' => '10'],
                //     'required' => true,
                //     'constraints' => new Constraints\NotBlank,
                // ]) 
                ->add($selector['name'], HiddenType::class, [
                    'constraints' => new Constraints\NotBlank,
                ])
                ->addEventListener(
                    FormEvents::PRE_SUBMIT,
                    function(FormEvent $event) use ($selector): void {
                        $data = $event->getData();
                        // TODO replace find with custom cb
                        if ($value = $data[$selector['name']]) {
                            $item = null;
                            if($value != "0" ) {
                                $item = $selector['repo']->find($value);
                            }
                            $data[$selector['name']] = $item;
                            $event->setData($data);
                        }
                    }
                )
            ;

            // add custom submit handler for search field, in order to populate choices
            // $elem = $builder->get('selector_search');
            // $elem->addEventListener(
            //     FormEvents::POST_SUBMIT, 
            //     function (PostSubmitEvent $event) use ($selector): void {
            //         $data = $event->getData();
            //         $form = $event->getForm();

            //         $form->getParent()->add($selector['name'], EntityType::class, [
            //             'label' => "asset.action.form.{$selector['name']}",
            //             'mapped' => $selector['mapped'] ?? true,
            //             'class' => $selector['class'],
            //             'choice_label' => $selector['label'],
            //             'choices' => $selector['model']($data, 10),
            //             'attr' => ['size' => '10'],
            //             'required' => true,
            //             'constraints' => new Constraints\NotBlank,
            //         ]);
            // });
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
            'confirm' => false,
            'not_before' => null,
            'usage_required' => false,
            'selector' => [],
        ]);
        
        $resolver->setAllowedTypes('confirm', 'bool');
        $resolver->setAllowedTypes('usage_required', 'bool');
        $resolver->setAllowedTypes('selector', 'array');
        $resolver->setAllowedTypes('not_before', ['null', DateTimeInterface::class]);
    }
}
