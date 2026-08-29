<?php

namespace App\Form;

use App\Entity\CaseFile;
use App\Enum\CaseSecrecy;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Validator\Constraints as Constraints;

/**
 * Form for case files.
 * 
 * @author Ben Brooksnieder
 */
class CaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('caseId', Field\TextType::class, [
                'label' => 'case.form.case_id',
                'required' => true,
            ])
            ->add('active', Field\CheckboxType::class, [
                'label' => 'case.form.active',
                'data' => true,
                'required' => true,
            ])
            ->add('secrecy', Field\EnumType::class, [
                'label' => 'case.form.secrecy',
                'class' => CaseSecrecy::class,
                'placeholder' => false,
                'expanded' => false,
                'multiple' => false,
                'choices' =>  CaseSecrecy::cases(),
                // 'choice_translation_domain' => false,
                'required' => false,
            ])
            ->add('description', Field\TextareaType::class, [
                'label' => 'case.form.description',
            ])
            ->add('save', Field\SubmitType::class, [
                'label' => 'case.form.save',
            ])
        ;

        if ($options['showOpenedOn']) {
            $builder->add('openedOn', Field\DateTimeType::class, [
                'label' => 'case.form.opened_on',
                'data' => new \DateTime(),
                'required' => true,
                'widget'       => 'single_text',
                'with_seconds' => true,
            ]);
            // set default value of case
            $builder->get('secrecy')->setData(CaseSecrecy::Confidential);
        }

        if ($options['closedOn_not_before']) {
            $builder->add('closedOn', Field\DateTimeType::class, [
                'label' => 'case.form.closed_on',
                'data' => new \DateTime(),
                'required' => true,
                'widget'       => 'single_text',
                'with_seconds' => true,
                'constraints' => new Constraints\GreaterThan($options['closedOn_not_before'])
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CaseFile::class,
            'showOpenedOn' => false,
            'closedOn_not_before' => null,
            'constraints' => [
                new UniqueEntity(fields: ['caseId'], message: 'case.error.duplicate'),
            ],
        ]);

        $resolver->setAllowedTypes('showOpenedOn', 'bool');
        $resolver->setAllowedTypes('showOpenedOn', 'bool');
        $resolver->setAllowedTypes('closedOn_not_before', ['null', \DateTimeInterface::class]);
    }
}
