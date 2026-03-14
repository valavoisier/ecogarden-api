<?php

namespace App\Entity;

use App\Repository\MoisRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MoisRepository::class)]
class Mois
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'Le numéro de mois est obligatoire.')]
    #[Assert\Range(min: 1, max: 12, notInRangeMessage: 'Le numéro de mois doit être compris entre 1 et 12.')]
    private ?int $numero = null;

    /**
     * @var Collection<int, Conseil>
        *
        * Côté inverse d'une relation ManyToMany : la propriété propriétaire
        * est Conseil::$mois (définie avec inversedBy: 'conseils').
        * Seules les modifications effectuées sur le côté propriétaire sont persistées
        * par Doctrine lors du flush(). Les helpers addConseil() / addMois()
        * synchronisent les deux côtés pour éviter les incohérences en mémoire (ORM).
        */
    #[ORM\ManyToMany(targetEntity: Conseil::class, mappedBy: 'mois')]
    private Collection $conseils;

    public function __construct()
    {
        $this->conseils = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumero(): ?int
    {
        return $this->numero;
    }

    public function setNumero(int $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * @return Collection<int, Conseil>
     */
    public function getConseils(): Collection
    {
        return $this->conseils;
    }

    public function addConseil(Conseil $conseil): static
    {
        if (!$this->conseils->contains($conseil)) {
            $this->conseils->add($conseil);
            $conseil->addMois($this);
        }

        return $this;
    }

    public function removeConseil(Conseil $conseil): static
    {
        if ($this->conseils->removeElement($conseil)) {
            $conseil->removeMois($this);
        }

        return $this;
    }
}
