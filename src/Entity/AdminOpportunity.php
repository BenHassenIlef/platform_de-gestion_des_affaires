<?php

namespace App\Entity;

use App\Repository\AdminOpportunityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AdminOpportunityRepository::class)]
class AdminOpportunity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $company = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $contact = null;

    #[ORM\Column(type: 'integer')]
    private ?int $value = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $closeDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $decision = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $notifiedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $originalOpportunityId = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getCompany(): ?string
    {
        return $this->company;
    }

    public function setCompany(?string $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(?int $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getCloseDate(): ?\DateTimeInterface
    {
        return $this->closeDate;
    }

    public function setCloseDate(?\DateTimeInterface $closeDate): self
    {
        $this->closeDate = $closeDate;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDecision(): ?string
    {
        return $this->decision;
    }

    public function setDecision(?string $decision): self
    {
        $this->decision = $decision;
        return $this;
    }

    public function getNotifiedAt(): ?\DateTimeInterface
    {
        return $this->notifiedAt;
    }

    public function setNotifiedAt(?\DateTimeInterface $notifiedAt): self
    {
        $this->notifiedAt = $notifiedAt;
        return $this;
    }

    public function getOriginalOpportunityId(): ?int
    {
        return $this->originalOpportunityId;
    }

    public function setOriginalOpportunityId(?int $originalOpportunityId): self
    {
        $this->originalOpportunityId = $originalOpportunityId;
        return $this;
    }
}
