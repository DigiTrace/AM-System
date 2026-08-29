<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Base type for list selections from dynamically queried tables.
 *
 * @author Ben Brooksnieder
 */
class ListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('selector', SearchType::class, [
                'label' => $options['selector_label'],
                'help' => $options['selector_help'],
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'style' => 'width: 400px',
                ],
            ])
            ->add('limit', ChoiceType::class, [
                'label' => null,
                'mapped' => false,
                'data' => 10,
                'choices' => [
                    10 => 10,
                    25 => 25,
                    50 => 50,
                ],
                'expanded' => false,
                'multiple' => false,
                'choice_translation_domain' => false,
            ])
            ->add('item', HiddenType::class, [
                'constraints' => $options['nullable'] ? new NotBlank() : [],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'selector_label' => null,
            'selector_help' => null,
            'nullable' => false,
            'nullable_label' => null,
            'label' => null,
            'label_size' => '',
            'api' => null,
            'max_height' => null,
            'identifier' => 'list',
            'default' => null,
        ]);

        $resolver->addAllowedTypes('selector_label', ['null', 'string']);
        $resolver->addAllowedTypes('selector_help', ['null', 'string']);
        $resolver->addAllowedTypes('nullable', ['bool']);
        $resolver->addAllowedTypes('nullable_label', ['null', 'string']);
        $resolver->addAllowedTypes('label_size', ['string']);
        $resolver->addAllowedTypes('api', ['string']);
        $resolver->addAllowedTypes('max_height', ['null', 'int']);
        $resolver->addAllowedTypes('identifier', ['string']);
        $resolver->addAllowedTypes('default', ['null', 'object']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['nullable'] = $options['nullable'];
        $view->vars['nullable_label'] = $options['nullable_label'];
        $view->vars['label_size'] = $options['label_size'];
        $view->vars['api'] = $options['api'];
        $view->vars['max_height'] = $options['max_height'];
        $view->vars['identifier'] = $options['identifier'];
        $view->vars['default'] = $options['default'];
    }
}
