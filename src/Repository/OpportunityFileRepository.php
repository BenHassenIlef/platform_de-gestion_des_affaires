<?php

namespace App\Repository;

use App\Entity\OpportunityFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OpportunityFile>
 *
 * @method OpportunityFile|null find($id, $lockMode = null, $lockVersion = null)
 * @method OpportunityFile|null findOneBy(array $criteria, array $orderBy = null)
 * @method OpportunityFile[]    findAll()
 * @method OpportunityFile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OpportunityFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OpportunityFile::class);
    }

    public function save(OpportunityFile $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OpportunityFile $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByOpportunity($opportunity): array
    {
        return $this->createQueryBuilder('opf')
            ->andWhere('opf.opportunity = :opportunity')
            ->setParameter('opportunity', $opportunity)
            ->orderBy('opf.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 