<?php

namespace App\Controller;

use App\Entity\Nutzer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type as FormField;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Validator\Constraints as SecurityConstraints;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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
            if (false !== $updated_user) {
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
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    private function getUserChangeForm($user)
    {
        $form = $this->createFormBuilder($user, ['attr' => [
            // 'onsubmit' => 'return alertbeforesubmit()',
            'autocomplete' => 'off',
            'method' => 'POST',
            'style' => 'max-width: 1440px',
        ]])
        ->add('id', FormField\HiddenType::class, [
            'required' => true,
        ])
        ->add('username', FormField\TextType::class, [
            'label' => 'user.form.username',
            'required' => true,
            'attr' => ['autocomplete' => 'change-username'],
        ])
        ->add('fullname', FormField\TextType::class, [
            'label' => 'user.form.fullname',
            'required' => true,
            'attr' => ['autocomplete' => 'change-fullname'],
        ])
        ->add('email', FormField\EmailType::class, [
            'label' => 'user.form.email',
            'required' => true,
            'attr' => ['autocomplete' => 'change-email'],
        ])
        ->add('notifyCaseCreation', FormField\ChoiceType::class, [
            'label' => 'user.form.notify_case_creation',
            'choices' => [
                'user.form.state_subscribed' => true,
                'user.form.state_unsubscribed' => false,
            ],
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
     *
     * @param Nutzer $updated_user updated user from form
     *
     * @return bool|Nutzer `false` if validation failed, otherwise updated user
     */
    private function processUserChangeForm(Nutzer $updated_user, EntityManagerInterface $entityManager)
    {
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
            ->setParameter(':id', $updated_user->getId())
            ->setParameter(':fullname', $updated_user->getFullname())
            ->setParameter(':username', $updated_user->getUsername())
            ->setParameter(':email', $updated_user->getEMail())
        ;

        $duplicates = $query->getArrayResult()[0][1];

        if ($duplicates > 0) {
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
        $form = $this->createFormBuilder(null, [
            'attr' => ['style' => 'max-width: 800px;'],
        ])
        ->add('old_password', FormField\PasswordType::class, [
            'attr' => ['autocomplete' => 'password'],
            'label' => 'user.form.old_password',
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(),
                new SecurityConstraints\UserPassword([
                    'message' => 'user.violation.incorrect_password',
                ]),
            ],
        ])
        // TODO add constraints for password e.g. capital letter
        // TODO exclude in own constraint collection, that can be used for adding users also
        ->add('new_password', FormField\PasswordType::class, [
            'attr' => ['autocomplete' => 'new-password'],
            'label' => 'user.form.new_password',
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(),
            ],
        ])
        ->add('new_password_repeat', FormField\PasswordType::class, [
            'attr' => ['autocomplete' => 'new-password-repeat'],
            'label' => 'user.form.new_password_repeat',
            'required' => true,
            'constraints' => [
                new Constraints\NotBlank(),
                // Check if new passwords match
                // TODO exclude as own constraint
                new Constraints\Callback(function ($new_password_repeat, ExecutionContextInterface $context, $payload) {
                    $form = $context->getRoot();
                    $new_password = $form->get('new_password')->getData();

                    if ($new_password_repeat !== $new_password) {
                        $context->buildViolation('user.violation.passwords_not_matching')
                        ->addViolation();
                    }
                }),
            ],
        ])
        ->add('save', FormField\SubmitType::class, [
            'label' => 'user.form.save',
            'attr' => ['class' => 'btn btn-primary '],
        ])
        ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $data = $form->getData();

            $hashednewPassword = $passwordHasher->hashPassword(
                $user,
                $data['new_password']
            );

            $user->setPassword($hashednewPassword);
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'user.form.password_change_successful');

            return $this->redirectToRoute('user_profile');
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @deprecated use `index` instead
     */
    #[Route('/profil/aendern', name: 'NutzerAenderung')]
    public function ChangeProfile(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->redirectToRoute('user_profile');
    }
}
