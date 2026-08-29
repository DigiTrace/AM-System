<?php

namespace App\Form\Asset;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints;

/**
 * Form for uploading pictures.
 *
 * @author Ben Brooksnieder
 */
class UploadPictureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('picture', Field\FileType::class, [
                'label' => 'form.picture.upload',
                'required' => false,
                'constraints' => [
                    new Constraints\File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/jpg',
                            // TODO why not png?
                        ],
                    ]),
                ],
            ])
            ->add('is_public', Field\CheckboxType::class, [
                'label' => 'form.picture.is_public',
                'required' => false,
            ])
            ->add('select_public', Field\ChoiceType::class, [
                'label' => 'form.picture.select_public',
                'required' => false,
                'placeholder' => false,
                'expanded' => true,
                'multiple' => false,
                'attr' => ['style' => 'display: none;'],
                'choice_translation_domain' => false,
                'choices' => $options['public_pictures'],
                'choice_label' => function ($choice, $key, $index) {
                    return $choice->getFilename();
                },
            ])
            ->add('save', Field\SubmitType::class, [
                'label' => 'form.apply',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'public_pictures' => [],
        ]);

        $resolver->setAllowedTypes('public_pictures', 'array');
    }
}
