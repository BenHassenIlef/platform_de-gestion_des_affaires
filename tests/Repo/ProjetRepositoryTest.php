<?php

namespace App\Tests\Repo;

use App\Entity\Projet;
use App\Repository\ProjetRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProjetRepositoryTest extends KernelTestCase
{
    private ?ProjetRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->repository = $kernel->getContainer()->get('doctrine')->getRepository(Projet::class);
    }

    public function testFindProjectsWithUpcomingDeadlines()
    {
        $projet = new Projet();
        $projet->setNom('Test Project')
            ->setSociete('Test Company')
            ->setDatePub(new \DateTime('now'))
            ->setDeadline((new \DateTime())->modify('-7 days'))
            ->setCahierDeCharge('Test Cahier de Charge')
            ->setSuivi('Test Suivi')
            ->setAvis('Test Avis')
            ->setMotif('Test Motif');



        $result = $this->repository->findProjectsWithUpcomingDeadlines($projet);

        $this->assertEquals($projet->getId(), $result);
    }
}
