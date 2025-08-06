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
use App\Repository\NotificationRepository;
use App\Repository\OpportunityFileRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Twig\Environment;
use App\Entity\OpportunityFile;

class RHDashboardController extends AbstractController
{
    #[Route('/rh/home', name: 'rh_home')]
    public function home(): Response
    {
        // Redirect to the main dashboard instead of showing duplicate page
        return $this->redirectToRoute('rh_dashboard');
    }

    #[Route('/rh/dashboard', name: 'rh_dashboard')]
        public function dashboard(OpportunityRepository $opportunityRepository, NotificationRepository $notificationRepository, OpportunityFileRepository $fileRepository): Response
    {
        $opportunities = $opportunityRepository->findAll();
        $notifications = $notificationRepository->findUnreadByUser($this->getUser());

        // Get files for each opportunity
        $opportunityFiles = [];
        foreach ($opportunities as $opportunity) {
            $opportunityFiles[$opportunity->getId()] = $fileRepository->findByOpportunity($opportunity);
        }

        return $this->render('rh_dashboard.html.twig', [
            'opportunities' => $opportunities,
            'notifications' => $notifications,
            'opportunityFiles' => $opportunityFiles
        ]);
    }

    #[Route('/rh/add-opportunity-page', name: 'rh_add_opportunity_page', methods: ['GET'])]
    public function addOpportunityPage(): Response
    {
        return $this->render('rh_add_opportunity.html.twig');
    }

    #[Route('/rh/mark-notification-read/{id}', name: 'rh_mark_notification_read', methods: ['POST'])]
    public function markNotificationAsRead(
        int $id,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $notification = $notificationRepository->find($id);
        
        if (!$notification || $notification->getRecipient() !== $this->getUser()) {
            return new JsonResponse(['success' => false, 'message' => 'Notification not found'], 404);
        }
        
        $notification->setIsRead(true);
        $entityManager->persist($notification);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }

    #[Route('/rh/add-opportunity', name: 'rh_add_opportunity', methods: ['POST'])]
    public function addOpportunity(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer, OpportunityFileRepository $fileRepository): Response
    {
        $opportunity = new Opportunity();
        $opportunity->setName($request->request->get('opportunityName'));
        $opportunity->setCompany($request->request->get('companyName'));
        $opportunity->setContact($request->request->get('contactPerson'));
        $opportunity->setValue((int)$request->request->get('opportunityValue'));
        $opportunity->setCloseDate(new \DateTime($request->request->get('closeDate'), new \DateTimeZone('Africa/Tunis')));
        $opportunity->setDescription($request->request->get('description') ?: '');
        $opportunity->setStatus('en cours');
        $opportunity->setCreatedBy($this->getUser());
        $opportunity->setNotifiedToAdmin(false);
        
        $entityManager->persist($opportunity);
        $entityManager->flush();
        
        // Handle PDF file upload if provided
        $uploadedFile = $request->files->get('pdfFile');
        if ($uploadedFile) {
            try {
                // Validate file type
                if ($uploadedFile->getMimeType() !== 'application/pdf') {
                    throw new \Exception('Only PDF files are allowed.');
                }
                
                // Validate file size (10MB limit)
                if ($uploadedFile->getSize() > 10 * 1024 * 1024) {
                    throw new \Exception('File size must be less than 10MB.');
                }
                
                // Generate unique filename
                $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate('Any-Latin; Latin-ASCII', $originalFilename);
                $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '', $safeFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.pdf';
                
                // Move file to uploads directory
                $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs/';
                if (!is_dir($uploadsDirectory)) {
                    mkdir($uploadsDirectory, 0777, true);
                }
                
                $uploadedFile->move($uploadsDirectory, $newFilename);
                
                // Create OpportunityFile entity
                $opportunityFile = new OpportunityFile();
                $opportunityFile->setFilename($newFilename);
                $opportunityFile->setOriginalFilename($uploadedFile->getClientOriginalName());
                $opportunityFile->setSize($uploadedFile->getSize());
                $opportunityFile->setOpportunity($opportunity);
                $opportunityFile->setUploadedBy($this->getUser());
                $opportunityFile->setUploadedAt(new \DateTime());
                
                $entityManager->persist($opportunityFile);
                $entityManager->flush();
                
                $this->addFlash('success', '📄 PDF file uploaded successfully!');
            } catch (\Exception $e) {
                $this->addFlash('warning', '⚠️ PDF upload failed: ' . $e->getMessage());
            }
        }
        
        $this->addFlash('success', '✅ Opportunity added successfully!');
        
        return $this->redirectToRoute('rh_dashboard');
    }

    #[Route('/rh/delete-opportunity/{id}', name: 'rh_delete_opportunity', methods: ['POST'])]
    public function deleteOpportunity(int $id, OpportunityRepository $opportunityRepository, EntityManagerInterface $entityManager, NotificationRepository $notificationRepository, OpportunityFileRepository $fileRepository): Response
    {
        $opportunity = $opportunityRepository->find($id);
        if ($opportunity) {
            try {
                // Supprimer d'abord tous les fichiers PDF associés à cette opportunité
                $files = $fileRepository->findBy(['opportunity' => $opportunity]);
                foreach ($files as $file) {
                    // Supprimer le fichier physique du serveur
                    $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs/' . $file->getFilename();
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $entityManager->remove($file);
                }
                
                // Supprimer toutes les notifications associées à cette opportunité
                $notifications = $notificationRepository->findBy(['opportunity' => $opportunity]);
                foreach ($notifications as $notification) {
                    $entityManager->remove($notification);
                }
                
                // Supprimer ensuite l'opportunité
                $entityManager->remove($opportunity);
                $entityManager->flush();
                
                $this->addFlash('success', '✅ Opportunity deleted successfully!');
                return $this->redirectToRoute('rh_dashboard');
            } catch (\Exception $e) {
                $this->addFlash('error', '❌ Error deleting opportunity: ' . $e->getMessage());
                return $this->redirectToRoute('rh_dashboard');
            }
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
        $opportunity->setCloseDate(new \DateTime($request->request->get('closeDate'), new \DateTimeZone('Africa/Tunis')));
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

    #[Route('/rh/upload-pdf/{id}', name: 'rh_upload_pdf', methods: ['POST'])]
    public function uploadPdf(Request $request, Opportunity $opportunity, EntityManagerInterface $entityManager, MailerInterface $mailer): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_RH');
        
        error_log('PDF Upload started for opportunity: ' . $opportunity->getName());
        error_log('FILES: ' . print_r($_FILES, true));
        
        $file = $request->files->get('pdfFile');
        
        if (!$file) {
            error_log('No file uploaded');
            return new JsonResponse(['success' => false, 'error' => 'No file uploaded']);
        }
        
        error_log('File received: ' . $file->getClientOriginalName() . ' (' . $file->getSize() . ' bytes)');
        
        if ($file->getMimeType() !== 'application/pdf') {
            error_log('Invalid file type: ' . $file->getMimeType());
            return new JsonResponse(['success' => false, 'error' => 'Only PDF files are allowed']);
        }
        

        
        try {
            // Create uploads directory if it doesn't exist
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs/';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0777, true);
            }
            
            // Generate unique filename
            $filename = uniqid() . '_' . $file->getClientOriginalName();
            
            // Move file to uploads directory
            $file->move($uploadsDir, $filename);
            
            // Save file info to database
            $opportunityFile = new OpportunityFile();
            $opportunityFile->setFilename($filename);
            $opportunityFile->setOriginalFilename($file->getClientOriginalName());
            $opportunityFile->setMimeType($file->getMimeType());
            $opportunityFile->setFileSize($file->getSize());
            $opportunityFile->setOpportunity($opportunity);
            $opportunityFile->setUploadedBy($this->getUser());
            
            $entityManager->persist($opportunityFile);
            $entityManager->flush();
            
            error_log('PDF file saved to database with ID: ' . $opportunityFile->getId());
            
            // Send email notification to admin
            try {
                error_log('Preparing to send email notification...');
                
                $email = (new Email())
                    ->from('benhassenilef20@gmail.com')
                    ->to('benhassenilef20@gmail.com')
                    ->subject('📄 New PDF Uploaded - Opportunity: ' . $opportunity->getName())
                    ->html($this->renderView('emails/pdf_upload_notification.html.twig', [
                        'opportunity' => $opportunity,
                        'file' => $opportunityFile,
                        'uploadedBy' => $this->getUser(),
                        'uploadDate' => new \DateTime(),
                        'app_url' => 'http://localhost:8000'
                    ]))
                    ->attachFromPath($uploadsDir . $filename, $file->getClientOriginalName());
                
                $mailer->send($email);
                error_log('Email notification sent successfully!');
            } catch (\Exception $e) {
                // Log email error but don't fail the upload
                error_log('Failed to send PDF upload notification email: ' . $e->getMessage());
                error_log('Email error details: ' . $e->getTraceAsString());
            }
            
            error_log('PDF upload completed successfully');
            return new JsonResponse(['success' => true]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Upload failed: ' . $e->getMessage()]);
        }
    }
    
    #[Route('/rh/get-pdf-files/{id}', name: 'rh_get_pdf_files', methods: ['GET'])]
    public function getPdfFiles(Request $request, Opportunity $opportunity, OpportunityFileRepository $fileRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_RH');
        
        // Get files from database
        $files = $fileRepository->findByOpportunity($opportunity);
        
        $fileData = [];
        foreach ($files as $file) {
            $fileData[] = [
                'id' => $file->getId(),
                'filename' => $file->getOriginalFilename(),
                'size' => $file->getFileSize(),
                'uploadedAt' => $file->getUploadedAt()->format('Y-m-d H:i:s'),
                'uploadedBy' => $file->getUploadedBy() ? $file->getUploadedBy()->getUsername() : 'Unknown'
            ];
        }
        
        return new JsonResponse(['success' => true, 'files' => $fileData]);
    }
    
    #[Route('/rh/download-pdf/{fileId}', name: 'rh_download_pdf', methods: ['GET'])]
    public function downloadPdf(Request $request, int $fileId, OpportunityFileRepository $fileRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');
        
        $file = $fileRepository->find($fileId);
        
        if (!$file) {
            throw $this->createNotFoundException('File not found');
        }
        
        // Check if user has access to this file (either RH or Admin)
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $file->getUploadedBy() !== $user) {
            throw $this->createAccessDeniedException('You do not have access to this file');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs/' . $file->getFilename();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found on disk');
        }
        
        return $this->file($filePath, $file->getOriginalFilename());
    }
    
    #[Route('/rh/delete-pdf/{fileId}', name: 'rh_delete_pdf', methods: ['POST'])]
    public function deletePdf(Request $request, int $fileId, OpportunityFileRepository $fileRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_RH');
        
        $file = $fileRepository->find($fileId);
        
        if (!$file) {
            return new JsonResponse(['success' => false, 'error' => 'File not found']);
        }
        
        // Delete file from disk
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs/' . $file->getFilename();
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Remove from database
        $entityManager->remove($file);
        $entityManager->flush();
        
        return new JsonResponse(['success' => true]);
    }
    
    #[Route('/admin/download-pdf/{fileId}', name: 'admin_download_pdf', methods: ['GET'])]
    public function adminDownloadPdf(Request $request, int $fileId, OpportunityFileRepository $fileRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $file = $fileRepository->find($fileId);
        
        if (!$file) {
            throw $this->createNotFoundException('File not found');
        }
        
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs/' . $file->getFilename();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found on server');
        }
        
        return $this->file($filePath, $file->getOriginalFilename());
    }
    
    #[Route('/rh/test-upload', name: 'rh_test_upload', methods: ['GET'])]
    public function testUpload(): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_RH');
        
        return new JsonResponse([
            'success' => true, 
            'message' => 'Upload system is working',
            'upload_dir' => $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs/'
        ]);
    }
}