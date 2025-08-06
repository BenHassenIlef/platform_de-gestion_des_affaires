<?php

namespace App\Entity;

use App\Repository\OpportunityRepository;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\User;
use App\Entity\OpportunityFile;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: OpportunityRepository::class)]
class Opportunity
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

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $probability = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $closeDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $createdBy = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => false])]
    private ?bool $notifiedToAdmin = false;

    #[ORM\OneToMany(mappedBy: 'opportunity', targetEntity: OpportunityFile::class, cascade: ['persist', 'remove'])]
    private Collection $files;

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

    public function getProbability(): ?int
    {
        return $this->probability;
    }

    public function setProbability(?int $probability): self
    {
        $this->probability = $probability;
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getNotifiedToAdmin(): ?bool
    {
        return $this->notifiedToAdmin;
    }

    public function setNotifiedToAdmin(?bool $notifiedToAdmin): self
    {
        $this->notifiedToAdmin = $notifiedToAdmin;

        return $this;
    }

    public function __construct()
    {
        $this->notifiedToAdmin = false;
        $this->files = new ArrayCollection();
    }

    /**
     * @return Collection<int, OpportunityFile>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(OpportunityFile $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setOpportunity($this);
        }

        return $this;
    }

    public function removeFile(OpportunityFile $file): self
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getOpportunity() === $this) {
                $file->setOpportunity(null);
            }
        }

        return $this;
    }
}
