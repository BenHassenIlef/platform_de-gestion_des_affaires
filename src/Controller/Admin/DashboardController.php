<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Entity\User;
use App\Repository\ProjetRepository;
use App\Task\SendProjectDeadlineNotificationTask;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Twig\Environment;

class DashboardController extends AbstractDashboardController
{
    private $security;
    private $twig;

    public function __construct(
        private AdminUrlGenerator $adminUrlGenerator, 
        Security $security, 
        Environment $twig
    ) {
        $this->security = $security;
        $this->twig = $twig;
    }

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $url = $this->adminUrlGenerator
            ->setController(ProjetCrudController::class)
            ->generateUrl();
        return $this->redirect($url);
    }

    #[Route('/test', name: 'test')]
    public function test(MessageBusInterface $bus, ProjetRepository $projetRepository, MailerInterface $mailer): Response
    {
        $task = new SendProjectDeadlineNotificationTask($bus, $projetRepository, $mailer, $this->twig);
        $response = $task->__invoke();

    
        return    $response ;
    }

    #[Route('/projects', name: 'projects')]
    public function showProjects(ProjetRepository $projetRepository): Response
    {
        $projects = $projetRepository->findAll();

        return $this->render('projects.html.twig', ['projects' => $projects]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('App');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Projets', 'fa fa-home');
        if ($this->security->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-person', User::class);
        }
    }
}
