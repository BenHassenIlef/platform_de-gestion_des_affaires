<?php
// src/Command/SendTestEmailCommand.php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendTestEmailCommand extends Command
{
    protected static $defaultName = 'app:send-test-email';

    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('app:send-test-email') // Add this explicitly if needed
            ->setDescription('Sends a test email to verify mailer configuration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (new Email())
            ->from('motez12367@gmail.com') // Use a valid sender email
            ->to('moetez.b.fredj@gmail.com') // Updated recipient email
            ->subject('Test Email')
            ->text('This is a test email.');

        try {
            $this->mailer->send($email);
            $output->writeln('Test email sent successfully!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('Failed to send test email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
