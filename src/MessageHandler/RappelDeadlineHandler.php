<?php

namespace App\MessageHandler;

use App\Message\RappelDeadline;
use App\Repository\ProjetRepository;
use App\Message\SendProjectDeadlineNotificationTask;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;


#[AsMessageHandler]
class RappelDeadlineHandler
{
    private MailerInterface $mailer;
    private $projetRepository;

    public function __construct(MailerInterface $mailer, ProjetRepository $projetRepository)
    {
        $this->mailer = $mailer;
        $this->projetRepository = $projetRepository;
    }

    public function __invoke(RappelDeadline $msg): void
    {
        $IDprojet = $msg->getId();
        $projet = $this->projetRepository->find($IDprojet);

        if ($projet) {
            $email = (new Email())
                ->from('motez12367@mail.com')
                ->to('moetez.b.fredj@gmail.com') // Replace with the actual recipient
                ->subject('RAPPEL')
                ->text("La date limite du projet " . $projet->getNom() . " est dans 7 jours");

            $this->mailer->send($email);
        } else {
            $email = (new Email())
                ->from('motez12367@mail.com')
                ->to('moetez.b.fredj@gmail.com') // Replace with the actual recipient
                ->subject('RAPPEL')
                ->text("Il n'y a aucun projet");

            $this->mailer->send($email);
        }
    }
}
