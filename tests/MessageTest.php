<?php

namespace App\Tests;

use App\Message\RappelDeadline;
use App\MessageHandler\RappelDeadlineHandler;
use App\Repository\ProjetRepository;
use App\SendProjectDeadlineNotificationTask\SendProjectDeadlineNotificationTask;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class MessageTest extends KernelTestCase
{
    public function testMessageDispatch()
    {
        self::bootKernel();
        $container = static::getContainer();

        /** @ var InMemoryTransport $transport */
        //$transport = $container->get('messenger.transport.async');
        //$transport->reset();

        /** @var MessageBusInterface $messageBus */
        $messageBus = $container->get(MessageBusInterface::class);

        /** @var ProjetRepository $projetRepository */
        $projetRepository = $this->createMock(ProjetRepository::class);

        $projetRepository->method('findProjectsWithUpcomingDeadlines')
            ->willReturn([new class
            {
                public function getId()
                {
                    return 1;
                }
                public function getNom()
                {
                    return 'Test Project';
                }
            }]);


        $task = new SendProjectDeadlineNotificationTask($messageBus, $projetRepository);
        $task->__invoke();


        $envelopes = $transport->get();
        $this->assertCount(1, $envelopes);
        $envelope = $envelopes[0] ?? null;

        $this->assertNotNull($envelope, 'The message was not dispatched.');
        $this->assertInstanceOf(RappelDeadline::class, $envelope->getMessage());
        $this->assertEquals(1, $envelope->getMessage()->getId());
    }

    public function testMessageHandler()
    {
        $mailer = $this->createMock(MailerInterface::class);
        $projetRepository = $this->createMock(ProjetRepository::class);

        $projet = new class
        {
            public function getId()
            {
                return 1;
            }
            public function getNom()
            {
                return 'Test Project';
            }
        };

        $projetRepository->method('find')
            ->willReturn($projet);
        $projetRepository->method('findProjectsWithUpcomingDeadlines')
            ->willReturn(true);

        $mailer->expects($this->once())->method('send');

        $handler = new RappelDeadlineHandler($mailer, $projetRepository);
        $handler(new RappelDeadline(1));
    }
}
