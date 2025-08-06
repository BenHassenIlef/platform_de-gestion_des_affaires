<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $user, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager(); // Récupère l'EntityManager
    
        $entityManager->persist($user); // Persiste l'entité
    
        if ($flush) {
            $entityManager->flush(); // Enregistre dans la base de données
        }
    }    

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(User $user, bool $flush = false): void
    {
        $entityManager = $this->getEntityManager();
        $entityManager->remove($user);
        if ($flush) {
            $entityManager->flush();
        }
    }

    /**
     * @return User[]
     */
    public function findInactiveSince(\DateTime $date)
    {
        return $this->createQueryBuilder('u')
            ->where('u.lastActivity < :date OR u.lastActivity IS NULL')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%' . $role . '%')
            ->getQuery()
            ->getResult();
    }
}