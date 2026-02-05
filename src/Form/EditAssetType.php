<?php

namespace App\Form;

use App\Entity\Asset;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\CaseFile;
use App\Enum\AssetCategory as Category;
use App\Validator\Barcode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints;

/**
 * @author Ben Brooksnieder
 */
class EditAssetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', Field\TextType::class, [
                'label' => 'asset.edit.form.name',
                'constraints' => new Constraints\NotBlank(),
                'required' => true,
            ])
            ->add('usage', Field\TextareaType::class, [
                'label' => 'asset.edit.form.usage',
                'required' => false,
            ])
            ->add('note', Field\TextareaType::class, [
                'label' => 'asset.edit.form.note',
                'required' => false,
            ])
            ->add('save', Field\SubmitType::class, [
                'label' => 'asset.edit.form.save',
            ])
        ;

        // add listener to add drive form depending on category
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (PostSetDataEvent $event): void {
            $data = $event->getData();
            $form = $event->getForm();

            // add drive form if asset is drive
            if ($data->isDrive()) {
                 $form->add('drive', DriveType::class);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
