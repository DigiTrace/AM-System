<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base component for entity search.
 *
 * @author Ben Brooksnieder
 */
class EntitySearchType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'attr' => ['id' => 'search_form'],
            'limit' => 25,
            'limit_choices' => [
                '25',
                '50',
                '100',
                '1000',
            ],
            'show_extended_search' => false,
        ]);

        $resolver->setAllowedTypes('limit', ['int', 'null']);
        $resolver->setAllowedTypes('limit_choices', 'array');
        $resolver->setAllowedTypes('show_extended_search', 'bool');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('search', Field\SearchType::class, [
            'required' => false,
            'attr' => ['size' => 75],
        ]);

        // limit selector
        $builder->add('limit', Field\ChoiceType::class, [
            'choices' => $options['limit_choices'],
            'choice_label' => fn ($choice, string $key, mixed $value) => $value,
            'choice_translation_domain' => false,
            'data' => $options['limit'],
            'attr' => ['onchange' => 'submit();'],
        ]);

        // invisible submit
        $builder->add('submit', Field\SubmitType::class, [
            'label' => 'es.form.search',
            'attr' => ['style' => 'display: none'],
        ]);

        // optional extended search form toggle button
        if (true === $options['show_extended_search']) {
            $builder->add('es', Field\ButtonType::class, [
                'label' => 'es.form.show',
                'attr' => [
                    'type' => 'button',
                    'onclick' => "$('#es_form_container').slideToggle(250)",
                ],
            ]);
        }
    }
}
