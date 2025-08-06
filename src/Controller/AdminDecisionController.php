<?php

namespace App\Controller;

use App\Entity\Opportunity;
use App\Entity\Notification;
use App\Entity\User;
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
class AdminDecisionController extends AbstractController
{
    #[Route('/admin/update-opportunity-decision', name: 'admin_update_opportunity_decision', methods: ['POST'])]
    public function updateOpportunityDecision(
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
} 