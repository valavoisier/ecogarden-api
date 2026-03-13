<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu du conseil est obligatoire.')]
    private ?string $contenu = null;

    /**
     * @var Collection<int, Mois>
     */
    #[ORM\ManyToMany(targetEntity: Mois::class, inversedBy: 'conseils')]
    private Collection $mois;

    public function __construct()
    {
        $this->mois = new ArrayCollection();
    }    

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;

        return $this;
    }

    /**
     * @return Collection<int, Mois>
     */
    public function getMois(): Collection
    {
        return $this->mois;
    }

    public function addMois(Mois $mois): static
    {
        if (!$this->mois->contains($mois)) {
            $this->mois->add($mois);
            $mois->addConseil($this);
        }

        return $this;
    }

    public function removeMois(Mois $mois): static
    {
        if ($this->mois->removeElement($mois)) {
            $mois->removeConseil($this);
        }

        return $this;
    }
}
