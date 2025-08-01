<?php

namespace App\Controller\Admin;

use App\Entity\Projet;
use App\Repository\ProjetRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class ProjetCrudController extends AbstractCrudController
{
    private $projetRepository;

    public function __construct(ProjetRepository $projetRepository)
    {
        $this->projetRepository = $projetRepository;
    }

    public static function getEntityFqcn(): string
    {
        return Projet::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('Nom'),
            TextField::new('societe'),
            DateField::new('DatePub'),
            DateField::new('deadline'),
            ChoiceField::new('Avis')->setChoices([
                'Intéressant' => 'Intéressant',
                'Non Intéressant' => 'Non Intéressant',
            ]),
            //TextField::new('cahierDeCharge')->setFormType(FileType::class),
            TextField::new('cahierDeCharge')->setFormType(FileType::class)->setFormTypeOptions([
                'data_class' => null, // Désactive la validation stricte
            ]),
            ChoiceField::new('suivi')->setChoices([
                'En attente' => 'en attente',
                'En cours' => 'en cours',
                'Terminé' => 'terminé',
                'Abandonné' => 'abandonné',
            ]),
            TextField::new('OffreTechnique')->setFormType(FileType::class),
            TextField::new('OffreAdministrative')->setFormType(FileType::class),
            TextField::new('PartieFinanciere')->setFormType(FileType::class),
            ChoiceField::new('Caution')->setChoices([
                'Oui' => 'Oui',
                'Non' => 'Non',
            ]),
            NumberField::new('montantCaution'),
            ChoiceField::new('etatCaution')->setChoices([
                'Prêt' => 'pret',
                'Non' => 'Non',
            ]),
            TextField::new('Motif'),
        ];

        return $fields;
    }
}
