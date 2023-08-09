<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;



class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'Nutzerprofil')]
    public function index(): Response
    {
        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
        ]);
    }



    #[Route('/profile/passwort', name: 'NutzerPasswordAenderung')]
    public function ChangePassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        

        $changepwform = $this->createFormBuilder(array('attr' => array('onsubmit' => "return alertbeforesubmit()")))
            ->add("oldPW", PasswordType::class, array('label' => 'security.changepw.oldPW','required' => true))
            ->add("newPW", PasswordType::class, array('label' => 'security.changepw.newPW','required' => true))
            ->add('save',SubmitType::class)
            ->getForm();


        $changepwform->handleRequest($request);
        if ($changepwform->isSubmitted() && $changepwform->isValid()) {
        
            $user = $this->getUser();

            if(!$passwordHasher->isPasswordValid($user, $changepwform->getData()['oldPW'])){
                $this->addFlash('danger',"security.changepw.oldPW.incorrect");
            }
            else{
                $hashednewPassword = $passwordHasher->hashPassword(
                    $user,
                    $changepwform->getData()['newPW']
                );

                $user->setPassword($hashednewPassword);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success',"security.changepw.newPW.set");
            }

        }
        

        return $this->render('profile/ChangePasswort.html.twig', array(
            'changePWform' => $changepwform->createView()
        ));
    }
}



    
    
    

    
    

