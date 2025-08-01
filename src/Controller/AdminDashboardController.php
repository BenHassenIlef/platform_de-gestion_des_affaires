<?php

namespace App\Controller;

use App\Entity\Opportunity;
use App\Repository\OpportunityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    public function index(OpportunityRepository $opportunityRepository, UserRepository $userRepository): Response
    {
        $opportunities = $opportunityRepository->findAll();
        $newUsers = $userRepository->findBy(['isNew' => true]);
        return $this->render('admin_dashboard.html.twig', [
            'opportunities' => $opportunities,
            'new_users' => $newUsers
        ]);
    }

    #[Route('/admin/add-user', name: 'admin_add_user', methods: ['POST'])]
    public function addUser(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $email = $request->request->get('email');
        $password = $request->request->get('password');
        $role = $request->request->get('role');

        // Check if user already exists
        if ($userRepository->findOneBy(['email' => $email])) {
            $this->addFlash('error', 'User already exists.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$role]);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setIsNew(true);
        $userRepository->save($user, true);

        $this->addFlash('success', 'User created successfully!');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/user/{id}/edit', name: 'admin_edit_user')]
    public function editUser(int $id, Request $request, UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email'));
            $user->setRoles([$request->request->get('role')]);
            if ($request->request->get('password')) {
                $user->setPassword($passwordHasher->hashPassword($user, $request->request->get('password')));
            }
            $userRepository->save($user, true);
            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('admin_dashboard');
        }
        return $this->render('edit_user.html.twig', ['user' => $user]);
    }

    #[Route('/admin/user/{id}/delete', name: 'admin_delete_user')]
    public function deleteUser(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if ($user) {
            $userRepository->remove($user, true);
            $this->addFlash('success', 'User deleted successfully!');
        }
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/user/{id}/mark-not-new', name: 'admin_mark_user_not_new')]
    public function markUserNotNew(int $id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if ($user) {
            $user->setIsNew(false);
            $userRepository->save($user, true);
            $this->addFlash('success', 'User marked as seen.');
        }
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/admin/new-users', name: 'admin_new_users')]
    public function newUsers(UserRepository $userRepository): Response
    {
        $newUsers = $userRepository->findBy(['isNew' => true]);
        $allUsers = $userRepository->findAll();
        return $this->render('admin_new_users.html.twig', [
            'new_users' => $newUsers,
            'all_users' => $allUsers
        ]);
    }

    #[Route('/admin/home', name: 'admin_home')]
    public function home(OpportunityRepository $opportunityRepository): Response
    {
        $opportunities = $opportunityRepository->createQueryBuilder('o')
            ->where('o.notifiedToAdmin = :notified')
            ->setParameter('notified', true)
            ->getQuery()
            ->getResult();
        return $this->render('admin_home.html.twig', [
            'opportunities' => $opportunities
        ]);
    }

    #[Route('/admin/profile', name: 'admin_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        if ($request->isMethod('POST')) {
            $name = $request->request->get('profile_name');
            if ($name !== null) {
                $user->setName($name);
                $userRepository->save($user, true);
            }
            $this->addFlash('success', 'Profil mis à jour !');
            return $this->redirectToRoute('admin_profile');
        }
        return $this->render('admin_profile.html.twig');
    }

    #[Route('/admin/change-password', name: 'admin_change_password')]
    public function changePassword(Request $request): Response
    {
        // Affiche un formulaire de changement de mot de passe
        return $this->render('admin/change_password.html.twig');
    }
} 