<?php

namespace App\Form;

use App\Enum\AssetState as State;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Extension\Core\Type as Field;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Ben Brooksnieder
 */
class BatchAssetActionType extends AbstractType
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    const neutralize = 99;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $allowedActions = array_map(fn($s) => $s->value(), State::getAllowedBatchActionStates());
        $allowedActions[] = static::neutralize;

        
        $builder
            ->add('state', Field\ChoiceType::class, [
                'label' => 'desc.status',
                'choices' => $allowedActions,
            ])
            ->add('searchbox', Field\TextType::class,  [
                'required' => false
            ])
            ->add('select_objects', Field\SubmitType::class)
            ->add('dueDate', Field\DateTimeType::class, [
                'label' => 'desc.action.done',
                'required' => true,
                'widget'=> 'single_text',
                #'format' => 'dd.MM.yyyy HH:mm',
                'with_seconds' => true,
                'data' => new \Datetime(),
            ])
            ->add('usage', Field\TextareaType::class);
        ;



        // $formModifier = function (FormInterface $form,  $stored_objects = null) {
           

        //     $form->add('contextthings', ChoiceType::class, array(
        //     'label' => 'contextthings',
        //     'choices' => $stored_objects,
        //     'required' => false,
        //     'placeholder' => false,
        //     'attr' => array("size" => "3")
        //     ));
        // };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(PreSetDataEvent $event) {
            $form = $event->getForm();
            $form->add('context', Field\ChoiceType::class, [
                'label' => 'contextthings',
                'choices' => [],
                'required' => false,
                'placeholder' => false,
                'attr' => ["size" => "3"],
            ]);
        });
        
        
        
        // $builder->get('newstatus')->addEventListener(
        //     FormEvents::POST_SUBMIT,
        //     function (\Symfony\Component\Form\FormEvent $event) use ($formModifier) {
        //         // It's important here to fetch $event->getForm()->getData(), as
        //         // $event->getData() will get you the client data (that is, the ID)
        //         $this->newstatus = $event->getForm()->getData();
                
                
        //     },900);
        
        // $builder->addEventListener(FormEvents::POST_SUBMIT, function(PostSubmitEvent $event) {
        //     $form = $event->getForm();
        //     $search = $form->get('searchbox')->getData();


        // });
        

        // $builder->get('searchbox')->addEventListener(
        //     FormEvents::POST_SUBMIT,
        //     function (\Symfony\Component\Form\FormEvent $event) use ($formModifier) {
        //         // It's important here to fetch $event->getForm()->getData(), as
        //         // $event->getData() will get you the client data (that is, the ID)
        //         $searchbox = $event->getForm()->getData();
                
        //         $em = $this->em;
                
        //         if($this->newstatus == helper::STATUS_IN_EINEM_BEHAELTER_GELEGT){
        //             $query = $em->createQuery('SELECT o '
        //                     . 'FROM App:Objekt o '
        //                     . "WHERE (o.name like :searchword "
        //                     . " OR o.barcode_id like :searchword )"
        //                     . " AND o.kategorie_id =".helper::KATEGORIE_BEHAELTER
        //                     . "AND o.status_id !=".helper::STATUS_VERNICHTET. " "
        //                     . "AND o.status_id !=".helper::STATUS_VERLOREN. " "
        //                     )->setParameter('searchword',"%".$searchbox."%")
        //                     ->setMaxResults(6); 

        //             $objects = $query->getResult();

        //             $entityarray= [];
        //             foreach($objects as $object){
        //                 $entityarray[$object->getBarcode()." | ".$object->getName()] = $object->getBarcode();
        //             }

        //             $formModifier($event->getForm()->getParent(), $entityarray);
        //         }
                
        //         if($this->newstatus == helper::STATUS_EINEM_FALL_HINZUGEFUEGT){
                    
        //             $query = $em->createQuery('SELECT f '
        //                 . 'FROM App:CaseFile f '
        //                 . "WHERE f.description like :search "
        //                 . "OR f.caseId like :search ")
        //                 ->setParameter('search',"%".$searchbox."%")
        //                 ->setMaxResults(6); 

        //             $cases = $query->getResult();

        //             $entityarray= [];
        //             foreach($cases as $case){
        //                 $entityarray[$case->getCaseId()." | ".$case->getBeschreibung()] = $case->getId();
        //             }

        //             $formModifier($event->getForm()->getParent(), $entityarray);
        //         }
                
                
        //     }
        // );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
