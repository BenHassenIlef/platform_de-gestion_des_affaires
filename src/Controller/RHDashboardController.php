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
    public function home(): Response
    {
        // Redirect to the main dashboard instead of showing duplicate page
        return $this->redirectToRoute('rh_dashboard');
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
    public function addOpportunity(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $opportunity = new Opportunity();
        $opportunity->setName($request->request->get('opportunityName'));
        $opportunity->setCompany($request->request->get('companyName'));
        $opportunity->setContact($request->request->get('contactPerson'));
        $opportunity->setValue((int)$request->request->get('opportunityValue'));
        $opportunity->setCloseDate(new \DateTime($request->request->get('closeDate')));
        $opportunity->setDescription($request->request->get('description') ?: '');
        $opportunity->setStatus('en cours');
        $opportunity->setCreatedBy($this->getUser());
        
        // Check if admin should be informed immediately
        $informAdmin = $request->request->has('informAdmin');
        $opportunity->setNotifiedToAdmin($informAdmin);

        $entityManager->persist($opportunity);
        $entityManager->flush();
        
        // Send email to admin if checkbox is checked
        if ($informAdmin) {
            try {
                $email = (new Email())
                    //->from($this->getParameter('app.email_sender'))
                    //->to('benhassenilef20@gmail.com')
                    ->from('benhassenilef20@gmail.com')
                    ->to('benhassenilef20@gmail.com')
                    ->subject('RH Notification - New Opportunity Created')
                    ->html($this->renderView('emails/rh_new_opportunity.html.twig', [
                        'rh_user' => $this->getUser(),
                        'opportunities' => [$opportunity]
                    ]));

                error_log('Attempting to send email to admin@gmail.com');
                $mailer->send($email);
                error_log('Email sent successfully!');
                $this->addFlash('success', 'Opportunity created and administrator has been notified!');
            } catch (\Exception $e) {
                error_log('Email error: ' . $e->getMessage());
                error_log('Email error details: ' . $e->getTraceAsString());
                $this->addFlash('success', 'Opportunity created successfully!');
                $this->addFlash('warning', 'Admin notification email could not be sent. Error: ' . $e->getMessage());
                $this->addFlash('info', 'Please check email configuration in .env.local or contact system administrator.');
            }
        } else {
            $this->addFlash('success', 'Opportunity added successfully!');
        }
        
        return $this->redirectToRoute('rh_home');
    }

    #[Route('/rh/delete-opportunity/{id}', name: 'rh_delete_opportunity', methods: ['POST'])]
    public function deleteOpportunity(int $id, OpportunityRepository $opportunityRepository, EntityManagerInterface $entityManager): Response
    {
        $opportunity = $opportunityRepository->find($id);
        if ($opportunity) {
            $entityManager->remove($opportunity);
            $entityManager->flush();
            $this->addFlash('success', '✅ Opportunity deleted successfully!');
        return $this->redirectToRoute('rh_dashboard');
        } else {
            $this->addFlash('error', '❌ Opportunity not found!');
            return $this->redirectToRoute('rh_dashboard');
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
    public function editOpportunity(int $id, Request $request, OpportunityRepository $opportunityRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
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
        
        // Check if admin should be informed
        $wasNotified = $opportunity->getNotifiedToAdmin();
        $shouldNotify = $request->request->has('informAdmin');
        $opportunity->setNotifiedToAdmin($shouldNotify);
        
        $entityManager->flush();
        
        // Send email to admin if checkbox is checked and wasn't previously notified
        if ($shouldNotify && !$wasNotified) {
            try {
                $email = (new Email())
                    ->from($this->getParameter('app.email_sender'))
                    ->to($this->getParameter('app.admin_email'))
                    ->subject('RH Notification - Updated Opportunity')
                    ->html($this->renderView('emails/rh_opportunity_update.html.twig', [
                        'rh_user' => $this->getUser(),
                        'opportunities' => [$opportunity]
                    ]));

                error_log('Attempting to send email to admin@gmail.com');
                $mailer->send($email);
                error_log('Email sent successfully!');
                return new JsonResponse(['success' => true, 'message' => 'Opportunity updated and administrator has been notified!']);
            } catch (\Exception $e) {
                error_log('Email error: ' . $e->getMessage());
                error_log('Email error details: ' . $e->getTraceAsString());
                return new JsonResponse([
                    'success' => true, 
                    'message' => 'Opportunity updated successfully!',
                    'warning' => 'Admin notification email could not be sent. Please check email configuration or contact system administrator.'
                ]);
            }
        }

        return new JsonResponse(['success' => true, 'message' => 'Opportunity updated successfully!']);
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
                
                // Mettre à jour le nom dans l'utilisateur actuel et dans la session
                $freshUser->setName($trimmedName);
                $user->setName($trimmedName); // Mettre à jour l'utilisateur en session également
                
                try {
                    $entityManager->flush();
                    error_log('Name saved successfully: ' . $trimmedName);
                    
                    // Vérifier que le nom a été sauvegardé
                    $entityManager->clear();
                    $updatedUser = $userRepository->find($freshUser->getId());
                    error_log('Updated user name: ' . $updatedUser->getName());
                    
                    // Rafraîchir la session avec les nouvelles données
                    $this->container->get('security.token_storage')->getToken()->setUser($updatedUser);
                    
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
                ->from($this->getParameter('app.email_sender'))
                ->to($this->getParameter('app.admin_email'))
                ->subject('Test Email from RH System - ' . date('Y-m-d H:i:s'))
                ->html('<h1>Test Email</h1><p>This is a test email from the RH system sent at ' . date('Y-m-d H:i:s') . '</p><p>If you receive this email, the email system is working correctly!</p>');

            error_log('Sending test email to: ' . $this->getParameter('app.admin_email'));
            $mailer->send($email);
            error_log('Test email sent successfully!');
            
            $this->addFlash('success', '✅ Test email sent successfully! Check your inbox at benhassenilef20@gmail.com');
            return $this->redirectToRoute('rh_dashboard');
        } catch (\Exception $e) {
            error_log('Test email error: ' . $e->getMessage());
            error_log('Test email error details: ' . $e->getTraceAsString());
            $this->addFlash('error', '❌ Test email failed: ' . $e->getMessage());
            return $this->redirectToRoute('rh_dashboard');
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
                ->from($this->getParameter('app.email_sender'))
                ->to($this->getParameter('app.admin_email'))
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
                $this->addFlash('warning', 'Les opportunités ont été marquées comme notifiées, mais l\'email n\'a pas pu être envoyé. Erreur: ' . $e->getMessage());
                $this->addFlash('info', 'Vérifiez la configuration email dans .env.local ou contactez l\'administrateur système.');
        }

        return $this->redirectToRoute('rh_home');
    }

    #[Route('/rh/inform-admin/{id}', name: 'rh_inform_admin', methods: ['POST'])]
    public function informAdminForOpportunity(int $id, OpportunityRepository $opportunityRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $opportunity = $opportunityRepository->find($id);
        
        if (!$opportunity) {
            $this->addFlash('error', 'Opportunity not found.');
            return $this->redirectToRoute('rh_dashboard');
        }

        if ($opportunity->getNotifiedToAdmin()) {
            $this->addFlash('info', 'This opportunity has already been notified to the administrator.');
            return $this->redirectToRoute('rh_dashboard');
        }

        $user = $this->getUser();
        
        // Mark the opportunity as notified
        $opportunity->setNotifiedToAdmin(true);
        $entityManager->flush();

        // Send email to administrator
        try {
            $email = (new Email())
                ->from($this->getParameter('app.email_sender'))
                ->to($this->getParameter('app.admin_email'))
                ->subject('RH Notification - New Opportunity: ' . $opportunity->getName())
                ->html($this->renderView('emails/rh_inform_single_opportunity.html.twig', [
                    'rh_user' => $user,
                    'opportunity' => $opportunity
                ]));

            error_log('Attempting to send email to admin about opportunity: ' . $opportunity->getName());
            $mailer->send($email);
            error_log('Email sent successfully!');
            $this->addFlash('success', '✅ Administrator has been notified about this opportunity.');
        } catch (\Exception $e) {
            error_log('Email error: ' . $e->getMessage());
            error_log('Email error details: ' . $e->getTraceAsString());
            $this->addFlash('warning', '⚠️ Opportunity marked as notified, but email could not be sent. Error: ' . $e->getMessage());
            $this->addFlash('info', 'Please check email configuration in .env.local or contact system administrator.');
        }

        return $this->redirectToRoute('rh_dashboard');
    }

    #[Route('/rh/inform-admin-multiple', name: 'rh_inform_admin_multiple', methods: ['POST'])]
    public function informAdminForMultipleOpportunities(Request $request, OpportunityRepository $opportunityRepository, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        $opportunityIds = $request->request->get('opportunityIds', []);
        
        if (empty($opportunityIds)) {
            return new JsonResponse(['success' => false, 'error' => 'No opportunities selected']);
        }

        $opportunities = $opportunityRepository->findBy(['id' => $opportunityIds]);
        $user = $this->getUser();
        $notifiedCount = 0;
        $alreadyNotifiedCount = 0;

        foreach ($opportunities as $opportunity) {
            if (!$opportunity->getNotifiedToAdmin()) {
                $opportunity->setNotifiedToAdmin(true);
                $notifiedCount++;
            } else {
                $alreadyNotifiedCount++;
            }
        }

        if ($notifiedCount > 0) {
            $entityManager->flush();
        }

        // Send email to administrator
        try {
            $email = (new Email())
                ->from($this->getParameter('app.email_sender'))
                ->to($this->getParameter('app.admin_email'))
                ->subject('RH Notification - Multiple New Opportunities')
                ->html($this->renderView('emails/rh_inform_multiple_opportunities.html.twig', [
                    'rh_user' => $user,
                    'opportunities' => $opportunities,
                    'notified_count' => $notifiedCount,
                    'already_notified_count' => $alreadyNotifiedCount
                ]));

            error_log('Attempting to send email to admin about multiple opportunities');
            $mailer->send($email);
            error_log('Email sent successfully!');
            
            $message = "✅ Administrator has been notified about {$notifiedCount} opportunity(ies)";
            if ($alreadyNotifiedCount > 0) {
                $message .= " ({$alreadyNotifiedCount} were already notified)";
            }
            
            return new JsonResponse(['success' => true, 'message' => $message]);
        } catch (\Exception $e) {
            error_log('Email error: ' . $e->getMessage());
            error_log('Email error details: ' . $e->getTraceAsString());
            
            $message = "⚠️ Opportunities marked as notified, but email could not be sent. Error: " . $e->getMessage();
            return new JsonResponse(['success' => false, 'error' => $message]);
        }
    }
}