<?php

namespace App\Entity;

use App\Repository\CategoriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriesRepository::class)]
class Categories
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[ORM\Constraint(length: 50, message: 'Le nom de la catégorie ne peut pas dépasser {{ limit }} caractères.')]
    #[ORM\NotBlank(message: 'Le nom de la catégorie ne peut pas être vide.')]
    private ?string $name = null;

    /**
     * @var Collection<int, Tabs>
     */
    #[ORM\ManyToMany(targetEntity: Tabs::class, mappedBy: 'categories')]
    private Collection $tabs_cat;
    
    public function __construct()
    {
        $this->tabs_cat = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function addTabsCat(Tabs $tabsCat): static
    {
        if (!$this->tabs_cat->contains($tabsCat)) {
            $this->tabs_cat->add($tabsCat);
        }
        return $this;
    }

    public function removeTabsCat(Tabs $tabsCat): static
    {
        $this->tabs_cat->removeElement($tabsCat);
        return $this;
    }

    public function setTabsCat(Collection $tabs_cat): static
    {
        $this->tabs_cat = $tabs_cat;
        return $this;
    }

    public function getTabsCat(): Collection
    {
        return $this->tabs_cat;
    }
}