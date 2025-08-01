<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Opportunity;
use App\Repository\OpportunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Twig\Environment;

class RHDashboardController extends AbstractController
{
    #[Route('/rh/home', name: 'rh_home')]
    public function home(OpportunityRepository $opportunityRepository): Response
    {
        $opportunities = $opportunityRepository->findAll();
        return $this->render('rh_home.html.twig', [
            'opportunities' => $opportunities
        ]);
    }

    #[Route('/rh/dashboard', name: 'rh_dashboard')]
    public function dashboard(OpportunityRepository $opportunityRepository): Response
    {
        $opportunities = $opportunityRepository->findAll();
        return $this->render('rh_dashboard.html.twig', [
            'opportunities' => $opportunities
        ]);
    }

    #[Route('/rh/add-opportunity-page', name: 'rh_add_opportunity_page', methods: ['GET'])]
    public function addOpportunityPage(): Response
    {
        return $this->render('rh_add_opportunity.html.twig');
    }

    #[Route('/rh/add-opportunity', name: 'rh_add_opportunity', methods: ['POST'])]
    public function addOpportunity(Request $request, EntityManagerInterface $entityManager): Response
    {
        $opportunity = new Opportunity();
        $opportunity->setName($request->request->get('opportunityName'));
        $opportunity->setCompany($request->request->get('companyName'));
        $opportunity->setContact($request->request->get('contactPerson'));
        $opportunity->setValue((int)$request->request->get('opportunityValue'));
        $opportunity->setCloseDate(new \DateTime($request->request->get('closeDate')));
        $opportunity->setDescription($request->request->get('description') ?: '');
        $opportunity->setStatus('en cours');
        $opportunity->setNotifiedToAdmin(false);
        $opportunity->setCreatedBy($this->getUser());

        $entityManager->persist($opportunity);
        $entityManager->flush();

        $this->addFlash('success', 'Opportunity added successfully!');
        return $this->redirectToRoute('rh_home');
    }

    #[Route('/rh/delete-opportunity/{id}', name: 'rh_delete_opportunity', methods: ['POST'])]
    public function deleteOpportunity(int $id, OpportunityRepository $opportunityRepository, EntityManagerInterface $entityManager): Response
    {
        $opportunity = $opportunityRepository->find($id);
        if ($opportunity) {
            $entityManager->remove($opportunity);
            $entityManager->flush();
            return new JsonResponse(['success' => true]);
        } else {
            return new JsonResponse(['success' => false, 'error' => 'Opportunity not found'], 404);
        }
    }

    #[Route('/rh/edit-opportunity-page/{id}', name: 'rh_edit_opportunity_page', methods: ['GET'])]
    public function editOpportunityPage(int $id, OpportunityRepository $opportunityRepository): Response
    {
        $opportunity = $opportunityRepository->find($id);
        if (!$opportunity) {
            $this->addFlash('error', "L'opportunité n'a pas été trouvée.");
            return $this->redirectToRoute('rh_home');
        }
        
        return $this->render('rh_edit_opportunity.html.twig', [
            'opportunity' => $opportunity
        ]);
    }

    #[Route('/rh/edit-opportunity/{id}', name: 'rh_edit_opportunity', methods: ['POST'])]
    public function editOpportunity(int $id, Request $request, OpportunityRepository $opportunityRepository, EntityManagerInterface $entityManager): Response
    {
        $opportunity = $opportunityRepository->find($id);
        if (!$opportunity) {
            return new JsonResponse(['success' => false, 'error' => 'Opportunity not found'], 404);
        }

        $opportunity->setName($request->request->get('opportunityName'));
        $opportunity->setCompany($request->request->get('companyName'));
        $opportunity->setContact($request->request->get('contactPerson'));
        $opportunity->setValue((int)$request->request->get('opportunityValue'));
        $opportunity->setCloseDate(new \DateTime($request->request->get('closeDate')));
        $opportunity->setDescription($request->request->get('description') ?: '');

        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/rh/profile', name: 'rh_profile', methods: ['GET', 'POST'])]
    public function profile(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Debug: Afficher l'utilisateur actuel
        error_log('Current user ID: ' . $user->getId());
        error_log('Current user email: ' . $user->getEmail());
        error_log('Current user name: ' . $user->getName());

        // Forcer la récupération de l'utilisateur depuis la base de données
        $freshUser = $userRepository->find($user->getId());
        if (!$freshUser) {
            error_log('User not found in database!');
            return $this->redirectToRoute('app_login');
        }

        error_log('Fresh user name: ' . $freshUser->getName());

        if ($request->isMethod('POST')) {
            $name = $request->request->get('profile_name');
            
            // Debug: Afficher les valeurs reçues
            error_log('Received name from form: ' . $name);
            error_log('Form data: ' . print_r($request->request->all(), true));
            
            if ($name !== null && trim($name) !== '') {
                $trimmedName = trim($name);
                error_log('Setting name to: ' . $trimmedName);
                
                $freshUser->setName($trimmedName);
                
                try {
                    $entityManager->flush();
                    error_log('Name saved successfully: ' . $trimmedName);
                    
                    // Vérifier que le nom a été sauvegardé
                    $entityManager->clear();
                    $updatedUser = $userRepository->find($freshUser->getId());
                    error_log('Updated user name: ' . $updatedUser->getName());
                    
                    $this->addFlash('success', 'Profile updated successfully!');
                } catch (\Exception $e) {
                    error_log('Error saving name: ' . $e->getMessage());
                    error_log('Stack trace: ' . $e->getTraceAsString());
                    $this->addFlash('error', 'Error updating profile: ' . $e->getMessage());
                }
            } else {
                error_log('Name is empty or null');
                $this->addFlash('error', 'Name cannot be empty!');
            }
            return $this->redirectToRoute('rh_profile');
        }
        return $this->render('rh_profile.html.twig');
    }

    #[Route('/rh/test-email', name: 'rh_test_email', methods: ['GET'])]
    public function testEmail(MailerInterface $mailer): Response
    {
        try {
            $email = (new Email())
                ->from('noreply@example.com')
                ->to('admin@gmail.com')
                ->subject('Test Email from RH System')
                ->html('<h1>Test Email</h1><p>This is a test email from the RH system.</p>');

            error_log('Sending test email...');
            $mailer->send($email);
            error_log('Test email sent successfully!');
            
            return new Response('Test email sent successfully! Check the logs.');
        } catch (\Exception $e) {
            error_log('Test email error: ' . $e->getMessage());
            return new Response('Test email failed: ' . $e->getMessage());
        }
    }

    #[Route('/rh/inform-admin', name: 'inform_admin', methods: ['POST'])]
    public function informAdmin(OpportunityRepository $opportunityRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $opportunities = $opportunityRepository->findAll();
        $user = $this->getUser();
        $notifiedCount = 0;

        foreach ($opportunities as $opportunity) {
            if (!$opportunity->getNotifiedToAdmin()) {
                $opportunity->setNotifiedToAdmin(true);
                $notifiedCount++;
            }
        }

        if ($notifiedCount > 0) {
            $entityManager->flush();
        }

        // Envoyer un email à l'administrateur
        try {
            $email = (new Email())
                ->from('noreply@example.com')
                ->to('admin@gmail.com') // Email de l'administrateur
                ->subject('RH Notification - New Opportunities Available')
                ->html($this->renderView('emails/rh_inform_notification.html.twig', [
                    'rh_user' => $user,
                    'opportunities_count' => $notifiedCount,
                    'opportunities' => $opportunities
                ]));

            error_log('Attempting to send email to admin@gmail.com');
            $mailer->send($email);
            error_log('Email sent successfully!');
            $this->addFlash('success', 'Bien reçu ! Les opportunités ont été envoyées à l\'administrateur.');
        } catch (\Exception $e) {
            error_log('Email error: ' . $e->getMessage());
            error_log('Email error details: ' . $e->getTraceAsString());
            $this->addFlash('success', 'Bien reçu ! Les opportunités ont été envoyées à l\'administrateur. (Email non envoyé: ' . $e->getMessage() . ')');
        }

        return $this->redirectToRoute('rh_home');
    }
} 