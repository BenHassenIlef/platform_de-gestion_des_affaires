<?php

namespace App\Controller;

use App\Entity\Opportunity;
use App\Entity\Notification;
use App\Entity\User;
use App\Entity\OpportunityFile;
use App\Repository\OpportunityRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminOpportunitiesController extends AbstractController
{
    #[Route('/admin/opportunities', name: 'admin_opportunities')]
    public function index(OpportunityRepository $opportunityRepository): Response
    {
        $opportunities = $opportunityRepository->findBy(['notifiedToAdmin' => true]);
        return $this->render('admin_opportunities.html.twig', [
            'opportunities' => $opportunities
        ]);
    }

    #[Route('/admin/update-decision/{id}', name: 'admin_update_decision', methods: ['POST'])]
    public function updateDecision(
        int $id,
        Request $request,
        OpportunityRepository $opportunityRepository,
        NotificationRepository $notificationRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $opportunity = $opportunityRepository->find($id);
        if (!$opportunity) {
            $this->addFlash('error', 'Opportunity not found.');
            return $this->redirectToRoute('admin_opportunities');
        }

        $decision = $request->request->get('decision');
        if (!$decision) {
            $this->addFlash('error', 'No decision provided.');
            return $this->redirectToRoute('admin_opportunities');
        }

        // Update opportunity status
        $opportunity->setStatus($decision);
        $entityManager->persist($opportunity);

        // Create notification for RH users
        $rhUsers = $userRepository->findByRole('ROLE_RH');
        
        foreach ($rhUsers as $rhUser) {
            $notification = new Notification();
            $notification->setMessage("Admin has made a decision on opportunity '{$opportunity->getName()}': " . ucfirst($decision));
            $notification->setType('decision');
            $notification->setRecipient($rhUser);
            $notification->setOpportunity($opportunity);
            $notification->setIsRead(false);
            
            $notificationRepository->save($notification);
        }

        $entityManager->flush();

        $this->addFlash('success', "Decision updated to: " . ucfirst($decision));
        return $this->redirectToRoute('admin_opportunities');
    }

    #[Route('/admin/update-opportunity-decision-ajax', name: 'admin_update_opportunity_decision_ajax', methods: ['POST'])]
    public function updateOpportunityDecisionAjax(
        Request $request,
        OpportunityRepository $opportunityRepository,
        NotificationRepository $notificationRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        $opportunityId = $data['opportunityId'] ?? null;
        $decision = $data['decision'] ?? null;
        
        if (!$opportunityId || !$decision) {
            return new JsonResponse(['success' => false, 'message' => 'Missing required data'], 400);
        }
        
        $opportunity = $opportunityRepository->find($opportunityId);
        if (!$opportunity) {
            return new JsonResponse(['success' => false, 'message' => 'Opportunity not found'], 404);
        }
        
        // Update opportunity status
        $opportunity->setStatus($decision);
        $entityManager->persist($opportunity);
        
        // Create notification for RH users
        $rhUsers = $userRepository->findByRole('ROLE_RH');
        
        foreach ($rhUsers as $rhUser) {
            $notification = new Notification();
            $notification->setMessage("Admin has made a decision on opportunity '{$opportunity->getName()}': " . ucfirst($decision));
            $notification->setType('decision');
            $notification->setRecipient($rhUser);
            $notification->setOpportunity($opportunity);
            $notification->setIsRead(false);
            
            $notificationRepository->save($notification);
        }
        
        $entityManager->flush();
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Decision updated successfully',
            'opportunity' => [
                'id' => $opportunity->getId(),
                'name' => $opportunity->getName(),
                'status' => $opportunity->getStatus()
            ]
        ]);
    }

    #[Route('/admin/get-rh-pdf-files/{id}', name: 'admin_get_rh_pdf_files', methods: ['GET'])]
    public function getRhPdfFiles(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        // Get files from session for now
        $session = $request->getSession();
        $uploadedFiles = $session->get('uploaded_files', []);
        
        // Filter files for this opportunity
        $filesForOpportunity = array_filter($uploadedFiles, function($file) use ($opportunity) {
            return $file['opportunityId'] == $opportunity->getId();
        });
        
        $fileData = [];
        foreach ($filesForOpportunity as $index => $file) {
            $fileData[] = [
                'id' => $index,
                'originalFilename' => $file['originalName'],
                'fileSize' => $file['size'],
                'uploadedAt' => $file['uploadedAt'],
                'uploadedBy' => $file['uploadedBy']
            ];
        }
        
        return new JsonResponse(['success' => true, 'files' => $fileData]);
    }
    
    #[Route('/admin/download-rh-pdf/{fileId}', name: 'admin_download_rh_pdf', methods: ['GET'])]
    public function downloadRhPdf(Request $request, int $fileId): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $session = $request->getSession();
        $uploadedFiles = $session->get('uploaded_files', []);
        
        if (!isset($uploadedFiles[$fileId])) {
            throw $this->createNotFoundException('File not found');
        }
        
        $file = $uploadedFiles[$fileId];
        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/pdfs/' . $file['filename'];
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found on disk');
        }
        
        return $this->file($filePath, $file['originalName']);
    }
} 