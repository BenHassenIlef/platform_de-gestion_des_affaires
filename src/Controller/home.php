<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class home extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        // Si l'utilisateur n'est pas connecté, redirige vers login
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }
        
        // Sinon, affiche la page home avec la navbar
        return $this->render('home.html.twig');
    }

    #[Route('/send-email', name: 'send_email')]
    public function sendEmail(MailerInterface $mailer): RedirectResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Créer l'email
        $email = (new Email())
            ->from('noreply@votreapp.com')
            ->to($user->getEmail())
            ->subject('Message de votre application')
            ->html('
                <h2>Bonjour ' . ($user->getName() ?: $user->getEmail()) . ' !</h2>
                <p>Ceci est un email automatique envoyé depuis votre application.</p>
                <p>Vous avez cliqué sur le bouton d\'envoi d\'email dans la page d\'accueil.</p>
                <p>Date et heure : ' . date('d/m/Y H:i:s') . '</p>
                <br>
                <p>Cordialement,<br>Votre équipe</p>
            ');

        try {
            // Envoyer l'email
            $mailer->send($email);
            
            // Ajouter un message de succès
            $this->addFlash('success', 'Email envoyé avec succès !');
        } catch (\Exception $e) {
            // En cas d'erreur, ajouter un message d'erreur
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email : ' . $e->getMessage());
        }

        // Rediriger vers la page d'accueil
        return $this->redirectToRoute('home');
    }
}
