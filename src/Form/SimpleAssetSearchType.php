<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Validator\Constraints as Constraints;

/**
 * @author Ben Brooksnieder
 */
class SimpleAssetSearchType extends AbstractType
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
            'label' => 'eas.form.search',
            'attr' => ['style' => 'display: none']
        ]);

        // optional extended search form toggle button
        $builder->add('eas', Field\ButtonType::class, [
            'label' => 'eas.form.show',
            'attr' => [
                'type' => 'button',
                'onclick' => "$('#eas_form_container').slideToggle(250)",
            ]
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
