<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\DBAL\Types\DateType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PasswordField;
use EasyCorp\Bundle\EasyAdminBundle\Field;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class UserCrudController extends AbstractCrudController
{
    private $passwordHasher;
    private $authorizationChecker;

    public function __construct(UserPasswordHasherInterface $passwordHasher, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->passwordHasher = $passwordHasher;
        $this->authorizationChecker = $authorizationChecker;
    }
    public static function getEntityFqcn(): string
    {
        return User::class;
    }
    public function createEntity(string $entityFqcn)
    {
        return new User();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) return;

        // Hash the password before persisting the user
        $hashedPassword = $this->passwordHasher->hashPassword(
            $entityInstance,
            $entityInstance->getPassword()
        );
        $entityInstance->setPassword($hashedPassword);

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) return;

        // Hash the password before updating the user
        $hashedPassword = $this->passwordHasher->hashPassword(
            $entityInstance,
            $entityInstance->getPassword()
        );
        $entityInstance->setPassword($hashedPassword);

        parent::updateEntity($entityManager, $entityInstance);
    }
    /* public function configureActions(Actions $actions): Actions
    {
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            // Allow all actions for admin
            return $actions;
        } else {
            // Disable all actions for non-admin users
            return $actions
                ->disable(Crud::PAGE_NEW, Crud::PAGE_EDIT);
        }                               
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('User')
            ->setEntityLabelInPlural('Users');
    }
 */

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('email'),
            TextField::new('password')
                ->setFormType(PasswordType::class),
            ChoiceField::new('roles')
                ->setChoices([
                    'User' => 'ROLE_USER',
                    'Admin' => 'ROLE_ADMIN',
                ])->allowMultipleChoices() //alech yjini warning Array to string conversion ki manhotch hedhi
                ->renderExpanded()
                ->renderAsBadges(),
        ];
    }
}
