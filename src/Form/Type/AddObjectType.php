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
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
//@Assert\Regex(
//          pattern="/DT(AS|HW|AK|HD)\\d{5}$/i",
//          htmlPattern="/DT(AS|HW|AK|HD)\\d{5}$/i",
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\NotBlank;
 use Symfony\Component\Validator\Constraints\NotNull;
        

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Controller\helper;


class AddObjectType extends AbstractType
{
    private $em;
    //private $newstatus;
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entityManager' => null,
        ]);
    }
   
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->em = $options['entityManager'];
       // $kategorien = helper::$kategorienToId;
        //$tempchoices = array_merge(helper::$statusToId,helper::$vstatusToId);
        
        /*$forbiddenstatus = array(helper::STATUS_EDITIERT,
                                 helper::STATUS_EINGETRAGEN,
                                 helper::STATUS_VERLOREN,
                                 helper::STATUS_VERNICHTET,
                                 helper::STATUS_FESTPLATTENIMAGE_GESPEICHERT);
        
        foreach($tempchoices as $status=>$statusvalue){
            if(in_array($statusvalue, $forbiddenstatus)){
                unset($tempchoices[$status]);
            }
        }*/
        
        /*$builder
                ->add('newstatus', ChoiceType::class, array(
            'label' => 'desc.status',
            'choices' => $tempchoices,
            ))
            ->add('searchbox', TextType::class, array('required' => false))
            ->add('select_objects', SubmitType::class)
            ->add('dueDate', DateTimeType::class,array('label' => 'desc.action.done',
                                                        'required' => true,
                                                        'widget'=> 'single_text',
                                                        'format' => 'dd.MM.yyyy HH:mm',
                                                        'data' => new\ Datetime(),))
            ->add('newdescription', TextareaType::class);
        ;*/
        
        
     
        
        
         $builder->add("barcode_id", TextType::class,array('label' => 'desc.oid', 
                                                            'invalid_message'=> "teshfhgfgfghfghft",
                                                            'attr' => array('autofocus' => true, 
                                                                            'placeholder' => 'DTXX00000',),
                                                            'constraints' => new Regex(array("pattern"=>"/DT(AS|HW|AK|HD)\\d{5}$/i",
                                                                                             "message" =>"barcode.validation.failed")),
                                                            ))
                ->add('name',  TextType::class,array('label' => 'desc.name', 'required' => true, 'constraints' => new NotBlank(),))
                ->add('verwendung',  TextareaType::class,array('label'=> 'desc.ousage',
                                                               'required' => false,))
                ->add('notiz',  TextareaType::class,array('label'=> 'desc.notice',
                                                               'required' => false,))
                /*->add('kategorie_id', ChoiceType::class,[
                        'label' => 'desc.category',
                        'choices' => $kategorien,
                        'attr' => array('size' => '2',
                                        'onchange' => 'return checkEnableFields()')])*/
                ->add('dueDate', DateTimeType::class,array('label' => 'when.object.arrived',
                                                        'required' => true,
                                                        'widget'=> 'single_text',
                                                        #'format' => 'dd.MM.yyyy HH:mm',
                                                        'data' => new \Datetime(),
                                                        'attr' => array(
                                                            'placeholder' => 'dd.MM.yyyy HH:mm',
                                                        )))
                ->add('bauart', TextType::class,array('label' => 'desc.type',
                                                      'required' => false,))
                ->add('formfaktor',  TextType::class,array('label' => 'desc.formfactor',
                                                           'required' => false,))
                ->add('groessealt',  IntegerType::class,array('label' => 'desc.bsize',
                                                           'required' => false,
                                                            ))
                ->add('groesse', ChoiceType::class,array(
                        'required' => false,
                        'label' => 'desc.size',
                        'choice_translation_domain' => false,
                        'choices' => array('' => '',
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
                                            '10000' => '10000')
                        ))
                ->add('hersteller',  TextType::class,array('label' => 'desc.producer',
                                                           'required' => false,))
                ->add('modell',  TextType::class,array('label' => 'desc.modell',
                                                       'required' => false,))
                ->add('sn',  TextType::class,array('required' => false,
                                                   'label' => 'desc.sn'))
                ->add('pn',  TextType::class,array('required' => false,
                                                   'label' => 'desc.pn',))
                ->add('anschluss',  TextType::class,array('label' => 'desc.connection','required' => false))
                ->add('save',SubmitType::class,array('label' => 'add.object'))
                ->add('saveandnew',SubmitType::class,array('label' => 'add.object.and.add.new.object'))
                ->add('saveandaddsimilar',SubmitType::class,array('label' => 'add.object.and.add.similar.object'))
                ->add('searchbox', TextType::class, array('required' => false))
                ->add('enablecase', CheckboxType::class, array('required' => false,'label' => 'desc.enable.relate.to.case'))
                 ->add('case', ChoiceType::class, array(
            'label' => 'case',
            'choices' => array(),
            'required' => false,
            'placeholder' => false,
            'attr' => array("size" => "3")
            ))
        ;
        
        
        
        

        $formModifierKategorie = function (FormInterface $form, $category = null) {
            if($category == null){
                $category = helper::$kategorienToId;
            }
            $form->add('kategorie_id', ChoiceType::class,[
                        'label' => 'desc.category',
                        'required' => true,
                        'choices' => $category,
                        'attr' => array('size' => '2'),
                        'constraints' => new NotNull()
                ]);
        };
        
        
          
        $formModifierCases = function (FormInterface $form, $cases = null) {
            $form->add('case', ChoiceType::class, array(
            'label' => 'case',
            'choices' => $cases,
            'required' => false,
            'placeholder' => false,
            'attr' => array("size" => "3")
            ));
        };
        
        
        
        $builder->addEventListener(
                
            FormEvents::PRE_SET_DATA,
            function (\Symfony\Component\Form\FormEvent $event) use ($formModifierCases) {
                // this would be your entity, i.e. SportMeetup
                //$data = $event->getData();

                $formModifierCases($event->getForm(), null);
            }
        );
        
        $builder->addEventListener(
                
            FormEvents::PRE_SET_DATA,
            function (\Symfony\Component\Form\FormEvent $event) use ($formModifierKategorie) {
                // this would be your entity, i.e. SportMeetup
                //$data = $event->getData();

                $formModifierKategorie($event->getForm(), null);
            }
        );
        
        
        
        /*$builder->get('newstatus')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($formModifier) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $this->newstatus = $event->getForm()->getData();
                
                
            },900);*/
        
        

        $builder->get('searchbox')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($formModifierCases) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $searchbox = $event->getForm()->getData();
                
                $em = $this->em;
                
               
                $query = $em->createQuery("SELECT f "
                    . "FROM App:Fall f "
                    . "WHERE f.beschreibung like :search "
                    . "OR f.case_id like :search "
                    . "ORDER BY f.zeitstempel_beginn DESC ")
                    ->setParameter('search',"%".$searchbox."%")
                    ->setMaxResults(6); 

                $cases = $query->getResult();

                $entityarray= [];
                foreach($cases as $case){
                    $entityarray[$case->getCaseId()." | ".$case->getBeschreibung()] = $case->getId();
                }

                $formModifierCases($event->getForm()->getParent(), $entityarray);
                
            }
        );
        
        
        $builder->get('barcode_id')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (\Symfony\Component\Form\FormEvent $event) use ($formModifierKategorie) {
                // It's important here to fetch $event->getForm()->getData(), as
                // $event->getData() will get you the client data (that is, the ID)
                $barcode = $event->getForm()->getData();
                
                //$kategorien = helper::$kategorienToId;
                $kategorien = array();
                if(stripos($barcode, "DTAK") !== FALSE){
                    $kategorien = array('category.record' => helper::$kategorienToId['category.record']);
                }
                if(stripos($barcode, "DTAS") !== FALSE){
                    $kategorien = array('category.exhibit' => helper::$kategorienToId['category.exhibit'],
                                        'category.exhibit.hdd' => helper::$kategorienToId['category.exhibit.hdd']);
                }
                if(stripos($barcode, "DTHW") !== FALSE){
                    $kategorien = array( 'category.equipment' => helper::$kategorienToId['category.equipment'],
                                        'category.container' => helper::$kategorienToId['category.container']);
                }
                if(stripos($barcode, "DTHD") !== FALSE){
                    $kategorien = array( 'category.hdd' => helper::$kategorienToId['category.hdd']);
                }
                
                
                
                

                //$entityarray= [];
                
                //$entityarray["test"] = "5";
                //$entityarray["testttt"] = "6";
                //foreach($cases as $case){
                //    $entityarray[$case->getCaseId()." | ".$case->getBeschreibung()] = $case->getId();
                //}
                
                //print_r($entityarray);

                $formModifierKategorie($event->getForm()->getParent(), $kategorien);
                
            }
        );
    }

    // ...
}
?>