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

use App\Entity\Nutzer;



class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'Nutzerprofil')]
    public function index(): Response
    {
        $user = $this->getUser();



        return $this->render('profile/index.html.twig', [
            'controller_name' => 'ProfileController',
            'user' => $user
        ]);
    }



    #[Route('/profil/passwort', name: 'NutzerPasswordAenderung')]
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
                return $this->redirectToRoute('Nutzerprofil');
            }

        }
        

        return $this->render('profile/ChangePasswort.html.twig', array(
            'changePWform' => $changepwform->createView()
        ));
    }


    #[Route('/profil/aendern', name: 'NutzerAenderung')]
    public function ChangeProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $changeform = $this->createFormBuilder(array('attr' => array('onsubmit' => "return alertbeforesubmit()")))
            ->add("username", TextType::class, 
                array('label' => 'security.login.username',
                      'required' => true, 
                      'attr' => array('value' => $user->getUsername())))
            ->add("fullname", TextType::class, 
                array('label' => 'security.login.fullname',
                      'required' => true,
                      'attr' => array('value' => $user->getFullname())))
            ->add("email", TextType::class, 
                array('label' => 'useremail',
                      'required' => true,
                      'attr' => array('value' => $user->getEmail())))
            ->add('save',SubmitType::class)
            ->getForm();


        $changeform->handleRequest($request);
        if ($changeform->isSubmitted() && $changeform->isValid()) {
        
            $arechangesperformed=0;
            $invalidInput=0;
            // If the fullname has changed, it has to validated, if the name is already in use 
            if($changeform->getData()['fullname'] != $user->getFullname())
            {
                $arechangesperformed=1;
                $query =  $entityManager->createQuery("select u from App:Nutzer u "
                                    . "where u.fullname like :fullname ");
                $query->setParameter(":fullname",$changeform->getData()['fullname']);
                $users = $query->getResult();
                if (count($users) > 0){
                    $invalidInput=1;
                } 
            }

            // If the username has changed, it has to validated, if the name is already in use 
            if($changeform->getData()['username'] != $user->getUsername())
            {
                $arechangesperformed=1;
                $query =  $entityManager->createQuery("select u from App:Nutzer u "
                                    . "where u.username like :username ");
                $query->setParameter(":username",$changeform->getData()['username']);
                $users = $query->getResult();
                if (count($users) > 0){
                    $invalidInput=1;
                } 
            }


            // If the email has changed, it has to validated, if the email is already in use 
            if($changeform->getData()['email'] != $user->getEMail())
            {
                $arechangesperformed=1;
                $query =  $entityManager->createQuery("select u from App:Nutzer u "
                                    . "where u.email like :email ");
                $query->setParameter(":email",$changeform->getData()['email']);
                $users = $query->getResult();
                if (count($users) > 0){
                    $invalidInput=1;
                } 
            }

             
            if ( $invalidInput == 1){
                $this->addFlash('danger',"profile.failed.changed.user.data.taken");

            } 
            else{
                // If changes are avaiable
                if($arechangesperformed == 1)
                {
                    $user->setUsername($changeform->getData()['username']);
                    $user->setFullname($changeform->getData()['fullname']);
                    $user->setEmail($changeform->getData()['email']);
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash('success',"profile.successful.changed");
                }
                return $this->redirectToRoute('Nutzerprofil');
            }
        }
        

        return $this->render('profile/ChangeProfile.html.twig', array(
            'changeform' => $changeform->createView()
        ));
    }
}



    
    
    

    
    

