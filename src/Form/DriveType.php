<?php

namespace App\Form;

use App\Entity\Drive;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSubmitEvent;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form for drives. Designed to be embedded by other forms.
 * 
 * @see App\Form\AddAssetType
 * @see App\Form\EditAssetType
 * 
 * @author Ben Brooksnieder
 */
class DriveType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('formFactor', Field\TextType::class, [
                'label' => 'drive.form.form_factor',
                'help' => 'drive.form.help.form_factor',
                'required' => false,
            ])
            ->add('type', Field\TextType::class, [
                'label' => 'drive.form.type',
                'help' => 'drive.form.help.type',
                'required' => false,
            ])
            ->add('size_choice', Field\ChoiceType::class, [
                'label' => 'drive.form.size',
                'help' => 'drive.form.help.size_dropdown',
                'choice_translation_domain' => false,
                'choices' => [
                    '' => '',
                    '16' => '16',
                    '32' => '32',
                    '64' => '64',
                    '128' => '128',
                    '250' => '250',
                    '500' => '500',
                    '1000' => '1000',
                    '2000' => '2000',
                    '3000' => '3000',
                    '4000' => '4000',
                    '5000' => '5000',
                    '6000' => '6000',
                    '8000' => '8000',
                    '10000' => '10000',
                ],
                'mapped' => false,
                'required' => false,
            ])
            ->add('size', Field\IntegerType::class, [
                'label' => 'drive.form.size',
                'help' => 'drive.form.help.size',
                'required' => false,
            ])
            ->add('manufacturer', Field\TextType::class, [
                'label' => 'drive.form.manufacturer',
                'required' => false,
            ])
            ->add('model', Field\TextType::class, [
                'label' => 'drive.form.model',
                'required' => false,
            ])
            ->add('serialNumber', Field\TextType::class, [
                'label' => 'drive.form.serial_number',
                'required' => false,
            ])
            ->add('productNumber', Field\TextType::class, [
                'label' => 'drive.form.product_number',
                'required' => false,
            ])
            ->add('connector', Field\TextType::class, [
                'label' => 'drive.form.connector',
                'help' => 'drive.form.help.connector',
                'required' => false,
            ])
        ;

        // add listener to handle custom size field to overwrite size selection
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (PreSubmitEvent $event): void {
            $data = $event->getData();

            if (!$data) {
                return;
            }

            if (empty($data['size'])) {
                $data['size'] = $data['size_choice'];
            }
            unset($data['size_costum']);
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Drive::class,
        ]);
    }
}
