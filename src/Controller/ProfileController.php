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
        
            

            # Pruefen, ob der neue Benutzer schon vorhanden ist:
            
            $query =  $entityManager->createQuery("select u from App:Nutzer u "
                                    . "where u.fullname like :fullname "
                                    . "OR u.email like :email "
                                    . "OR u.username like :username ");

            $query->setParameter(":fullname",$changeform->getData()['fullname']);
            $query->setParameter(":username",$changeform->getData()['username']);
            $query->setParameter(":email",$changeform->getData()['email']);
            $users = $query->getResult();

             
            if (count($users) > 0){
                $this->addFlash('danger',"profile.failed.changed.user.data.taken");

            } 
            else{
                $user->setUsername($changeform->getData()['username']);
                $user->setFullname($changeform->getData()['fullname']);
                $user->setEmail($changeform->getData()['email']);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success',"profile.successful.changed");
                return $this->redirectToRoute('Nutzerprofil');
            }
        }
        

        return $this->render('profile/ChangeProfile.html.twig', array(
            'changeform' => $changeform->createView()
        ));
    }
}



    
    
    

    
    

