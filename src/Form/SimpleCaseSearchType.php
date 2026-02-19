<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type as Field;

/**
 * Form for searching for cases.
 * 
 * @author Ben Brooksnieder
 */
class SimpleCaseSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('search', Field\SearchType::class, [
            'required' => false,
            'label' => false,
            'attr' => ['size' => 75],
        ])
        ->setAction($options['search_action']);

        // limit selector
        $builder->add('limit', Field\ChoiceType::class, [
            'choices' => [
                '25' => '25',
                '50' => '50',
                '100' => '100',
                '1000' => '1000',
            ],
            'label' => false,
            'data' => $options['limit'],
            'attr' => ['onchange' => 'submit();'],
        ]);

        // invisible submit
        $builder->add('suchen', Field\SubmitType::class, [
            'attr' => ['style' => 'display: none']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'limit' => 25,
            'search_action' => '',
        ]);

        $resolver->setAllowedTypes('limit', ['int', 'null']);
        $resolver->setAllowedTypes('search_action', 'string');
    }
}
