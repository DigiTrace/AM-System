<?php
   // AM-System
   // Copyright (C) 2019 Robert Krasowski
   // This program was created during an internship at DigiTrace GmbH
   // Read LIZENZ.txt for full notice

   // This program is free software: you can redistribute it and/or modify
   // it under the terms of the GNU General Public License as published by
   // the Free Software Foundation, either version 3 of the License, or
   // (at your option) any later version.

   // This program is distributed in the hope that it will be useful,
   // but WITHOUT ANY WARRANTY; without even the implied warranty of
   // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   // GNU General Public License for more details.

   // You should have received a copy of the GNU General Public License
   // along with this program.  If not, see <http://www.gnu.org/licenses/>.

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Controller\helper;

class ActionChooseType extends AbstractType
{
    private $em;
    private $newstatus;
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entityManager' => null,
        ]);
    }
   
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['entityManager'];
        
        $tempchoices = array_merge(helper::$statusToId,helper::$vstatusToId);
        
        $forbiddenstatus = array(helper::STATUS_EDITIERT,
                                 helper::STATUS_EINGETRAGEN,
                                 helper::STATUS_VERLOREN,
                                 helper::STATUS_VERNICHTET,
                                 helper::STATUS_FESTPLATTENIMAGE_GESPEICHERT);
        
        foreach($tempchoices as $status=>$statusvalue){
            if(in_array($statusvalue, $forbiddenstatus)){
                unset($tempchoices[$status]);
            }
        }
        
        $builder
                ->add('newstatus', ChoiceType::class, array(
            'label' => 'desc.status',
            'choices' => $tempchoices,
            ))
            ->add('searchbox', TextType::class, array('required' => false))
            ->add('select_objects', SubmitType::class)
            ->add('dueDate', DateTimeType::class,array('label' => 'desc.action.done',
                                                        'required' => true,
                                                        'widget'=> 'single_text',
                                                        #'format' => 'dd.MM.yyyy HH:mm',
                                                        'with_seconds' => true,
                                                        'data' => new \Datetime(),))
            ->add('newdescription', TextareaType::class);
        ;

        $formModifier = function (FormInterface $form,  $stored_objects = null) {
           

            $form->add('contextthings', ChoiceType::class, array(
            'label' => 'contextthings',
            'choices' => $stored_objects,
            'required' => false,
            'placeholder' => false,
            'attr' => array("size" => "3")
            ));
        };

        $builder->addEventListener(
                
            FormEvents::PRE_SET_DATA,
            function (\Symfony\Component\Form\FormEvent $event) use ($formModifier) {
                // this would be your entity, i.e. SportMeetup
                $data = $event->getData();

                $formModifier($event->getForm(), null);
            }
        );
        
        
        
        $builder->get('newstatus')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $this->newstatus = $event->getForm()->getData();
                
                
            },900);
        
        

        $builder->get('searchbox')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $searchbox = $event->getForm()->getData();
                
                $em = $this->em;
                
                if($this->newstatus == helper::STATUS_IN_EINEM_BEHAELTER_GELEGT){
                    $query = $em->createQuery('SELECT o '
                            . 'FROM App:Objekt o '
                            . "WHERE (o.Name like :searchword "
                            . " OR o.Barcode_id like :searchword )"
                            . " AND o.Kategorie_id =".helper::KATEGORIE_BEHAELTER
                            . "AND o.Status_id !=".helper::STATUS_VERNICHTET. " "
                            . "AND o.Status_id !=".helper::STATUS_VERLOREN. " "
                            )->setParameter('searchword',"%".$searchbox."%")
                            ->setMaxResults(6); 

                    $objects = $query->getResult();

                    $entityarray= [];
                    foreach($objects as $object){
                        $entityarray[$object->getBarcode()." | ".$object->getName()] = $object->getBarcode();
                    }

                    $formModifier($event->getForm()->getParent(), $entityarray);
                }
                
                if($this->newstatus == helper::STATUS_EINEM_FALL_HINZUGEFUEGT){
                    
                    $query = $em->createQuery('SELECT f '
                        . 'FROM App:Fall f '
                        . "WHERE f.Beschreibung like :search "
                        . "OR f.case_id like :search ")
                        ->setParameter('search',"%".$searchbox."%")
                        ->setMaxResults(6); 

                    $cases = $query->getResult();

                    $entityarray= [];
                    foreach($cases as $case){
                        $entityarray[$case->getCaseId()." | ".$case->getBeschreibung()] = $case->getId();
                    }

                    $formModifier($event->getForm()->getParent(), $entityarray);
                }
                
                
            }
        );
    }

    // ...
}
?>