<?php

namespace App\Repository;

use App\Entity\Projet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    public function findProjetsDeadline(\DateTime $deadline): array
    {
        // Fetch projects where the deadline is within the next 7 days
        return $this->createQueryBuilder('p')
            ->where('p.deadline <= :deadline')
            ->setParameter('deadline', $deadline)
            ->andWhere('p.deadline <= CURRENT_DATE()')
            ->getQuery()
            ->getResult();
    }
}

