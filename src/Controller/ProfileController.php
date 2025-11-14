<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\Form\Extension\Core\Type as FormField;
use Symfony\Component\Validator\Constraints as Constrains;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Nutzer;


/**
 * Controller for profile management, e.g. change password etc.
 * 
 * @author Robert Krasowski
 * @author Ben Brooksnieder
 */
class ProfileController extends AbstractController
{

    /**
     * Show profile information and allow editing of attributes.
     */
    #[Route('/profil', name: 'user_profile')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->getUserChangeForm($user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // user ids must match
            if ($user->getID() !== intval($data->getId())) {
                throw $this->createAccessDeniedException();
            }

            $updated_user = $this->processUserChangeForm($data, $entityManager);
            if (false !== $updated_user){
                $this->addFlash('success', 'user.form.edit_successul');
                return $this->redirectToRoute('user_profile');
            }

            // change was not successful
            $this->addFlash('danger', 'user.form.error.duplicate');
        }

        $user = $this->getUser();
        return $this->render('user/index.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
            'controller_name' => 'ProfileController',
        ]);
    }

    /**
     * Generate form to update user.
     * @param mixed $user
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getUserChangeForm($user) {
        $form = $this->createFormBuilder($user, ['attr' => [
            // 'onsubmit' => 'return alertbeforesubmit()',
            'autocomplete' => 'off',
            'method' => 'POST',
        ]])
        ->add('id', FormField\HiddenType::class, [
            'required' => true,
        ])
        ->add('username', FormField\TextType::class, [
            'label' => 'user.form.username',
            'required' => true,
        ])
        ->add('fullname', FormField\TextType::class, [
            'label' => 'user.form.fullname',
            'required' => true,
        ])
        ->add('email', FormField\EmailType::class, [
            'label' => 'user.form.email',
            'required' => true,
        ])
        ->add('notifyCaseCreation', FormField\ChoiceType::class, [
            'label' => 'user.form.notify_case_creation',
            'choices' => [
                'user.form.state_subscribed' => true,
                'user.form.state_unsubscribed' => false,
            ]
        ])
        ->add('save', FormField\SubmitType::class, [
            'label' => 'user.form.apply',
            'attr' => ['class' => 'btn btn-primary '],
        ])
        ->add('reset', FormField\ResetType::class, [
            'label' => 'user.form.reset',
        ])
        ->getForm();
        
        return $form;
    }

    /**
     * Validates updated users values and if valid, update user. 
     * @param \App\Entity\Nutzer $updated_user Updated user from form.
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @return bool|Nutzer `false` if validation failed, otherwise updated user.
     */
    private function processUserChangeForm(Nutzer $updated_user, EntityManagerInterface $entityManager) {
        // test whether new values are allowed and not already in use
        $query_string = <<<SQL
        SELECT count(u) FROM App:Nutzer u 
        WHERE u.id != :id AND (
            u.fullname LIKE :fullname OR
            u.username LIKE :username OR
            u.email LIKE :email
        )
        SQL;

        $query = $entityManager->createQuery($query_string)
            ->setParameter(":id", $updated_user->getId())
            ->setParameter(":fullname", $updated_user->getFullname())
            ->setParameter(":username", $updated_user->getUsername())
            ->setParameter(":email", $updated_user->getEMail())
        ;

        $duplicates = $query->getArrayResult()[0][1];

        if ($duplicates > 0){
            return false;
        }

        // update user
        $entityManager->persist($updated_user);
        $entityManager->flush();

        return $updated_user;
    }




    #[Route('/profil/passwort', name: 'user_change_password')]
    public function changeUserPassword(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createFormBuilder([
            'attr' => ['autocomplete' => 'off']
        ])
        ->add("old_password", FormField\PasswordType::class, [
            'attr' => ['autocomplete' => 'password'],
            'label' => "user.form.old_password",
            'required' => true,
        ])
        ->add("new_password", FormField\PasswordType::class, [
            'label' => "user.form.new_password",
            'required' => true,
        ])
        ->add("new_password_repeat", FormField\PasswordType::class, [
            'label' => "user.form.new_password_repeat",
            'required' => true,
            'constraints' => [
                new Constrains\NotBlank(),
                
            ],
        ])
        ->add('save', FormField\SubmitType::class, [
            'label' => 'user.form.apply',
            'attr' => ['class' => 'btn btn-primary '],
        ])
        ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
        
            $user = $this->getUser();

            if($passwordHasher->isPasswordValid($user, $form->getData()['old_password'])){
                
                $hashednewPassword = $passwordHasher->hashPassword(
                    $user,
                    $form->getData()['newPW']
                );
                
                $user->setPassword($hashednewPassword);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success',"security.changepw.newPW.set");
                return $this->redirectToRoute('user_profile');
            }
            
            $this->addFlash('danger',"user.form.error.incorrect_password");
        }
        

        return $this->render('user/ChangePasswort.html.twig', array(
            'changePWform' => $form->createView()
        ));
    }


    /**
     * @deprecated Use `index` instead.
     */
    #[Route('/profil/aendern', name: 'NutzerAenderung')]
    public function ChangeProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->redirectToRoute('user_profile');
    }
}



    
    
    

    
    

