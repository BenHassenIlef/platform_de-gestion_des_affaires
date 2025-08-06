<?php

namespace App\Controller;

use App\Entity\Opportunity;
use App\Entity\OpportunityFile;
use App\Repository\OpportunityRepository;
use App\Repository\OpportunityFileRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_RH')]
class FileUploadController extends AbstractController
{
    #[Route('/rh/upload-file/{opportunityId}', name: 'rh_upload_file', methods: ['POST'])]
    public function uploadFile(
        int $opportunityId,
        Request $request,
        OpportunityRepository $opportunityRepository,
        OpportunityFileRepository $fileRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $opportunity = $opportunityRepository->find($opportunityId);
        
        if (!$opportunity) {
            return new JsonResponse(['success' => false, 'error' => 'Opportunity not found'], 404);
        }

        // Check if opportunity is approved
        if ($opportunity->getStatus() !== '✅ Approved') {
            return new JsonResponse(['success' => false, 'error' => 'Files can only be uploaded for approved opportunities'], 400);
        }

        $uploadedFile = $request->files->get('file');
        
        if (!$uploadedFile) {
            return new JsonResponse(['success' => false, 'error' => 'No file uploaded'], 400);
        }

        // Validate file size (max 10MB)
        if ($uploadedFile->getSize() > 10 * 1024 * 1024) {
            return new JsonResponse(['success' => false, 'error' => 'File size must be less than 10MB'], 400);
        }

        // Validate file type
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png'];
        $fileExtension = strtolower($uploadedFile->getClientOriginalExtension());
        
        if (!in_array($fileExtension, $allowedTypes)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)], 400);
        }

        try {
            // Generate unique filename
            $filename = uniqid() . '_' . time() . '.' . $fileExtension;
            
            // Create uploads directory if it doesn't exist
            $uploadsDir = $this->getParameter('kernel.project_dir') . '/public/uploads/opportunities';
            if (!is_dir($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
            }

            // Move uploaded file
            $uploadedFile->move($uploadsDir, $filename);

            // Create file entity
            $opportunityFile = new OpportunityFile();
            $opportunityFile->setFilename($filename);
            $opportunityFile->setOriginalFilename($uploadedFile->getClientOriginalName());
            $opportunityFile->setMimeType($uploadedFile->getMimeType());
            $opportunityFile->setFileSize($uploadedFile->getSize());
            $opportunityFile->setOpportunity($opportunity);
            $opportunityFile->setUploadedBy($this->getUser());

            $entityManager->persist($opportunityFile);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'File uploaded successfully',
                'file' => [
                    'id' => $opportunityFile->getId(),
                    'originalName' => $opportunityFile->getOriginalFilename(),
                    'size' => $opportunityFile->getFileSize(),
                    'uploadedAt' => $opportunityFile->getUploadedAt()->format('Y-m-d H:i')
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Failed to upload file: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/rh/download-file/{fileId}', name: 'rh_download_file', methods: ['GET'])]
    public function downloadFile(
        int $fileId,
        OpportunityFileRepository $fileRepository
    ): Response {
        $file = $fileRepository->find($fileId);
        
        if (!$file) {
            throw $this->createNotFoundException('File not found');
        }

        $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/opportunities/' . $file->getFilename();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found on disk');
        }

        return $this->file($filePath, $file->getOriginalFilename());
    }

    #[Route('/rh/delete-file/{fileId}', name: 'rh_delete_file', methods: ['POST'])]
    public function deleteFile(
        int $fileId,
        OpportunityFileRepository $fileRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $file = $fileRepository->find($fileId);
        
        if (!$file) {
            return new JsonResponse(['success' => false, 'error' => 'File not found'], 404);
        }

        try {
            // Delete physical file
            $filePath = $this->getParameter('kernel.project_dir') . '/public/uploads/opportunities/' . $file->getFilename();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete from database
            $entityManager->remove($file);
            $entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'File deleted successfully']);

        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Failed to delete file: ' . $e->getMessage()], 500);
        }
    }
} 