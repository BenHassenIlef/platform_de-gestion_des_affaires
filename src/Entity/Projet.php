<?php

namespace App\Entity;

use App\Repository\ProjetRepository;
use App\Validator\Deadline;
use App\Validator\DeadlineValidator;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\ClassMetadata as SerializerMappingClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata as MappingClassMetadata;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;

#[ORM\Entity(repositoryClass: ProjetRepository::class)]
class Projet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $Nom = null;

    #[ORM\Column(length: 50)]
    private ?string $societe = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $DatePub = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    //#[Assert\Date(message: "Please enter a valid date")]
    //#[Assert\GreaterThan(value: "today", message: "The date cannot be in the past")]
    private ?\DateTimeInterface $deadline = null;

    #[ORM\Column(length: 50)]
    private ?string $avis = null;

    #[ORM\Column(length: 255)]
    private ?string $cahierDeCharge = null;

    #[ORM\Column(length: 255)]
    private ?string $motif = null;



    #[ORM\Column(length: 50)]
    private ?string $suivi = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $OffreTechnique = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $OffreAdministrative = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $PartieFinanciere = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $Caution = null;

    #[ORM\Column(nullable: true)]
    private ?string $montantCaution = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $etatCaution = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->Nom;
    }

    public function setNom(string $Nom): static
    {
        $this->Nom = $Nom;

        return $this;
    }

    public function getSociete(): ?string
    {
        return $this->societe;
    }

    public function setSociete(string $societe): static
    {
        $this->societe = $societe;

        return $this;
    }

    public function getDatePub(): ?\DateTimeInterface
    {
        return $this->DatePub;
    }

    public function setDatePub(\DateTimeInterface $DatePub): static
    {
        $this->DatePub = $DatePub;

        return $this;
    }

    public function getDeadline(): ?\DateTimeInterface
    {
        return $this->deadline;
    }

    public function setDeadline(\DateTimeInterface $deadline): static
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getcahierDeCharge(): ?string
    {
        return $this->cahierDeCharge;
    }

    public function setcahierDeCharge(string $cahierDeCharge): self
    {
        $this->cahierDeCharge = $cahierDeCharge;

        return $this;
    }

    public function getSuivi(): ?string
    {
        return $this->suivi;
    }

    public function setSuivi(string $suivi): static
    {
        $this->suivi = $suivi;

        return $this;
    }
    public function getAvis(): ?string
    {
        return $this->avis;
    }

    public function setavis(String $avis): static
    {
        $this->avis = $avis;

        return $this;
    }
    public function getmotif(): ?string
    {
        return $this->motif;
    }

    public function setmotif(String $motif): static
    {
        $this->motif = $motif;

        return $this;
    }
    /*public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('deadline', new NotBlank());
        $metadata->addPropertyConstraint('deadline', new Deadline(['mode' => 'loose']));
    }*/

    public function getOffreTechnique(): ?string
    {
        return $this->OffreTechnique;
    }

    public function setOffreTechnique(?string $OffreTechnique): static
    {
        $this->OffreTechnique = $OffreTechnique;

        return $this;
    }

    public function getOffreAdministrative(): ?string
    {
        return $this->OffreAdministrative;
    }

    public function setOffreAdministrative(?string $OffreAdministrative): static
    {
        $this->OffreAdministrative = $OffreAdministrative;

        return $this;
    }

    public function getPartieFinanciere(): ?string
    {
        return $this->PartieFinanciere;
    }

    public function setPartieFinanciere(?string $PartieFinanciere): static
    {
        $this->PartieFinanciere = $PartieFinanciere;

        return $this;
    }

    public function getCaution(): ?string
    {
        return $this->Caution;
    }

    public function setCaution(?string $Caution): static
    {
        $this->Caution = $Caution;

        return $this;
    }

    public function getMontantCaution(): ?string
    {
        return $this->montantCaution;
    }

    public function setMontantCaution(?string $montantCaution): static
    {
        $this->montantCaution = $montantCaution;

        return $this;
    }

    public function getEtatCaution(): ?string
    {
        return $this->etatCaution;
    }

    public function setEtatCaution(?string $etatCaution): static
    {
        $this->etatCaution = $etatCaution;

        return $this;
    }
}
