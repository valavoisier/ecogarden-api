<?php

namespace App\Entity;

use App\Repository\ConseilRepository;
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

    #[ORM\Column(type: 'json')]
    #[Assert\Count(min: 1, minMessage: 'Un conseil doit être associé à au moins un mois.')]
    #[Assert\All([
        new Assert\Type(type: 'integer', message: 'Chaque mois doit être un entier.'),
        new Assert\Range(min: 1, max: 12, notInRangeMessage: 'Le mois doit être compris entre 1 et 12.'),
    ])]
    private array $mois = [];

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

    public function getMois(): array
    {
        return $this->mois;
    }

    public function setMois(array $mois): static
    {
        $this->mois = $mois;

        return $this;
    }
}
