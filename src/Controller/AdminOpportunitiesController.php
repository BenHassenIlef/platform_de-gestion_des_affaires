<?php

namespace App\Controller;

use App\Entity\Opportunity;
use App\Repository\OpportunityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $opportunities = $opportunityRepository->findAll();
        return $this->render('admin_opportunities.html.twig', [
            'opportunities' => $opportunities
        ]);
    }

    #[Route('/admin/opportunity/{id}/update-decision', name: 'admin_update_decision', methods: ['POST'])]
    public function updateDecision(
        int $id, 
        Request $request, 
        OpportunityRepository $opportunityRepository, 
        EntityManagerInterface $entityManager
    ): Response {
        $opportunity = $opportunityRepository->find($id);
        
        if (!$opportunity) {
            $this->addFlash('error', 'Opportunity not found!');
            return $this->redirectToRoute('admin_opportunities');
        }

        $decision = $request->request->get('decision');
        
        if (!in_array($decision, ['approved', 'rejected', 'pending', 'under_review'])) {
            $this->addFlash('error', 'Invalid decision value!');
            return $this->redirectToRoute('admin_opportunities');
        }

        $opportunity->setStatus($decision);
        $entityManager->flush();

        $this->addFlash('success', 'Decision updated successfully!');
        return $this->redirectToRoute('admin_opportunities');
    }
} 