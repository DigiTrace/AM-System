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

namespace App\Controller;

use App\Entity\Nutzer;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Controller for managing users. Admin only (@see security.yaml).
 *
 * @author Robert Krasowski
 * @author Ben Brooksnieder
 */
class AdminController extends AbstractController
{
    /**
     * Display list of all users.
     */
    #[Route(data: '/admin/nutzeruebersicht', name: 'user_overview')]
    public function getUserSummary(ManagerRegistry $doctrine, Request $request)
    {
        $userrepo = $doctrine->getRepository(Nutzer::class);
        $users = $userrepo->findAll();

        return $this->render(
            'admin/user_overview.html.twig',
            ['users' => $users]
        );
    }

    /**
     * Enabled/Disable user.
     */
    #[Route(data: '/admin/user/enable', name: 'set_enable_user')]
    public function setUserEnableAction(
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        Request $request,
    ) {
        $csrf = $request->request->get('_token');
        $username = $request->request->get('username');
        $enable = filter_var($request->request->get('enable'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        // if invalid csrf token, return 404
        if (!$this->isCsrfTokenValid('set_enable_user', $csrf)) {
            throw $this->createNotFoundException();
        }

        // validate input
        $errors = $validator->validate([
            'username' => $username,
            'enable' => $enable,
        ], new Assert\Collection([
            'username' => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
            ],
            'enable' => new Assert\NotNull(),
        ]));
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 401);
        }

        // fetch and update user
        $repo = $entityManager->getRepository(Nutzer::class);
        $user = $repo->findOneBy([
            'username' => $username,
        ]);

        if ($user) {
            $user->setEnabled($enable);
            $entityManager->flush();
        } else {
            return new JsonResponse(['errors' => 'user not found'], 404);
        }

        return new JsonResponse(['username' => $username, 'enabled' => $enable], 200);
    }

    /**
     * Enabled/Disable case subscription for users.
     */
    #[Route(data: '/admin/user/subscription', name: 'set_case_subscription_user')]
    public function setCaseSubscriptionAction(
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        Request $request,
    ) {
        $csrf = $request->request->get('_token');
        $username = $request->request->get('username');
        $enable = filter_var($request->request->get('enable'), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        // if invalid csrf token, return 404
        if (!$this->isCsrfTokenValid('set_case_subscription_user', $csrf)) {
            throw $this->createNotFoundException();
        }

        // validate input
        $errors = $validator->validate([
            'username' => $username,
            'enable' => $enable,
        ], new Assert\Collection([
            'username' => [
                new Assert\Type('string'),
                new Assert\NotBlank(),
            ],
            'enable' => new Assert\NotNull(),
        ]));
        if (count($errors) > 0) {
            return new JsonResponse(['errors' => (string) $errors], 401);
        }

        // fetch and update user
        $repo = $entityManager->getRepository(Nutzer::class);
        $user = $repo->findOneBy([
            'username' => $username,
        ]);

        if ($user) {
            $user->setNotifyCaseCreation($enable);
            $entityManager->flush();
        } else {
            return new JsonResponse(['errors' => 'user not found'], 404);
        }

        return new JsonResponse(['username' => $username, 'notfiyCaseCreation' => $enable], 200);
    }

    /**
     * Show form to add new user or process new user form.
     */
    #[Route('/admin/adduser', name: 'add_user')]
    public function addUser(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new Nutzer();

        $addUserForm = $this->createFormBuilder($user, ['attr' => [
            'onsubmit' => 'return alertbeforesubmit()',
            'autocomplete' => 'off',
        ]])
        ->add('username', TextType::class, [
            'label' => 'security.username',
            'required' => true,
            'attr' => ['autocomplete' => 'off'],
        ])
        ->add('fullname', TextType::class, [
            'label' => 'security.fullname',
            'required' => true,
            'attr' => ['autocomplete' => 'off'],
        ])
        ->add('email', EmailType::class, [
            'label' => 'security.email',
            'required' => true,
            'attr' => ['autocomplete' => 'off'],
        ])
        ->add('plainpassword', PasswordType::class, [
            'label' => 'security.password',
            'required' => true,
            'attr' => ['autocomplete' => 'off'],
        ])
        ->add('save', SubmitType::class, [
            'label' => 'security.add_user',
        ])
        ->getForm();

        $addUserForm->handleRequest($request);
        if ($addUserForm->isSubmitted() && $addUserForm->isValid()) {
            // Pruefen, ob der neue Benutzer schon vorhanden ist:
            $query = $entityManager->createQuery('select u from App:Nutzer u '
                                    .'where u.fullname like :fullname '
                                    .'OR u.email like :email '
                                    .'OR u.username like :username ');

            $query->setParameter(':fullname', $user->getFullname());
            $query->setParameter(':username', $user->getUsername());
            $query->setParameter(':email', $user->getEmail());
            $users = $query->getResult();

            if (count($users) > 0) {
                $this->addFlash('danger', 'security.duplicate_user');
            } else {
                $hashednewPassword = $passwordHasher->hashPassword(
                    $user,
                    $user->getPlainPassword()
                );
                $user->setRoles(['ROLE_USER']);

                $user->setPassword($hashednewPassword);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'security.user_added');
            }
        }

        return $this->render('admin/add_user.html.twig', [
            'form' => $addUserForm->createView(),
        ]);
    }
}
