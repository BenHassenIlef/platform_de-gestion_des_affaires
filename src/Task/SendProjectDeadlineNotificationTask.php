<?php

namespace App\Task;

use App\Entity\Projet;
use App\Message\ProjectDeadlineNotification;
use App\Message\RappelDeadline;
use App\MessageHandler\RappelDeadlineHandler;
use App\Repository\ProjetRepository;
use DateTime;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class SendProjectDeadlineNotificationTask
 {
    private MessageBusInterface $bus;
    private ProjetRepository $projetRepository;
    private MailerInterface $mailer;
    private Environment $twig;

    public function __construct(MessageBusInterface $bus, ProjetRepository $projetRepository, MailerInterface $mailer, Environment $twig)
    {
        $this->bus = $bus;
        $this->projetRepository = $projetRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function __invoke(): Response
{
    $dn = new \DateTime();
    $dn->modify('+7 day');

    $projets = $this->projetRepository->findProjetsDeadline($dn);

    if (!empty($projets)) {
        foreach ($projets as $projet) {
            try {
                $email = (new Email())
                    ->from('motez12367@mail.com')
                    ->to('moetez.b.fredj@gmail.com')
                    ->subject('RAPPEL')
                    ->html('
                    <html>
                    <head>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #333;
                            }
                            .container {
                                width: 80%;
                                margin: 0 auto;
                                padding: 20px;
                                background-color: #f9f9f9;
                                border-radius: 10px;
                                border: 1px solid #ddd;
                            }
                            .header {
                                text-align: center;
                                padding-bottom: 20px;
                            }
                            .header h1 {
                                font-size: 24px;
                                color: #2c3e50;
                            }
                            .content {
                                font-size: 16px;
                            }
                            .content p {
                                margin: 10px 0;
                            }
                            .content .highlight {
                                color: #e74c3c;
                                font-weight: bold;
                            }
                            .footer {
                                text-align: center;
                                padding-top: 20px;
                                font-size: 12px;
                                color: #999;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>Rappel de Projet</h1>
                            </div>
                            <div class="content">
                                <p>Bonjour,</p>
                                <p>La date limite du projet <span class="highlight">' . $projet->getNom() . '</span> est dans 5 jours.</p>
                                <p><b>Date limite :</b> ' . $projet->getDeadline()->format('Y-m-d') . '</p>
                                <p><b>Société :</b> ' . $projet->getSociete() . '</p>
                                <p>Veuillez prendre les mesures nécessaires pour respecter le délai.</p>
                            </div>
                            <div class="footer">
                                <p>Ceci est un message automatique, merci de ne pas y répondre.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ');
                $this->mailer->send($email);
            } catch (RuntimeException $e) {
                // Log or handle the exception
            }
        }
    } else {
        try {
            $email = (new Email())
                ->from('motez12367@mail.com')
                ->to('moetez.b.fredj@gmail.com')
                ->subject('RAPPEL')
                ->html('
                    <html>
                    <head>
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                line-height: 1.6;
                                color: #333;
                            }
                            .container {
                                width: 80%;
                                margin: 0 auto;
                                padding: 20px;
                                background-color: #f9f9f9;
                                border-radius: 10px;
                                border: 1px solid #ddd;
                            }
                            .header {
                                text-align: center;
                                padding-bottom: 20px;
                            }
                            .header h1 {
                                font-size: 24px;
                                color: #2c3e50;
                            }
                            .content {
                                font-size: 16px;
                            }
                            .content p {
                                margin: 10px 0;
                            }
                            .footer {
                                text-align: center;
                                padding-top: 20px;
                                font-size: 12px;
                                color: #999;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h1>Rappel de Projet</h1>
                            </div>
                            <div class="content">
                                <p>Bonjour,</p>
                                <p>Il n\'y a actuellement aucun projet à rappeler.</p>
                            </div>
                            <div class="footer">
                                <p>Ceci est un message automatique, merci de ne pas y répondre.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ');
            $this->mailer->send($email);
        } catch (RuntimeException $e) {
            // Log or handle the exception
        }
    }

    return new Response('
    <html>
    <head>
        <style>
            body {
                font-family: "Roboto", sans-serif;
                background-color: #f4f4f4;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                color: #2c3e50;
            }
            .alert-container {
                text-align: center;
                background-color: #ffffff;
                padding: 50px;
                border-radius: 12px;
                border: 2px solid #bdc3c7;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
            .alert-container h1 {
                font-size: 36px;
                color: #e74c3c;
                margin: 0 0 20px;
                font-weight: 700;
            }
            .alert-container p {
                font-size: 18px;
                margin: 0;
                color: #34495e;
            }
        </style>
    </head>
    <body>
        <div class="alert-container">
            <h1>Deadline Alert!</h1>
            <p>Emails processed successfully.</p>
        </div>
    </body>
    </html>
');

    
}

 }

    /*
       // $s = new RappelDeadlineHandler($this->mailer, $projectData[0]);
        var_dump("le nom du projet a envoyer un email");
        var_dump($projectData[0]['nom']);

        try {

        if ($projectData[0]) {
            $email = (new Email())
                ->from('motez12367@mail.com')
                ->to('moetez.b.fredj@gmail.com')
                ->subject('RAPPEL')
                ->text("La date limite du projet " . $projectData[0]['nom'] . " est dans 7 jours");

               // var_dump($email);

            $this->mailer->send($email);
            var_dump("Email sent successfully 9 1.");

        } else {
            $email = (new Email())
                ->from('motez12367@mail.com')
                ->to('moetez.b.fredj@gmail.com')
                ->subject('RAPPEL')
                ->text("Il n'y a aucun projet");

            $this->mailer->send($email);
            var_dump("Email sent successfully 2.");
        }

    }catch(RuntimeException $e){
        var_dump($e->getMessage());
    }


    
    
    return new Response("Email sent successfully 2." );
    }
    
 }



*/



            //$s=new (new RappelDeadlineHandler($this->mailer, $this->projetRepository));

           //$a=new (new RappelDeadline(1));
           // var_dump($projet);
            //$this->bus->dispatch(new RappelDeadlineHandler($this->mailer, $this->projetRepository));
           // $s->__invoke($a);

  // return new Response(
        //    $this->twig->render('debug.html.twig', ['data' => $projectData])
        //);



