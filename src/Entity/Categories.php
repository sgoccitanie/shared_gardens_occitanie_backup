<?php

namespace App\Entity;

use App\Repository\CategoriesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CategoriesRepository::class)]
class Categories
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: false)]
    #[Assert\Length(min: 3, max: 50, minMessage: 'Le nom de la catégorie doit comporter au moins {{ limit }} caractères.', maxMessage: 'Le nom de la catégorie ne peut pas dépasser {{ limit }} caractères.')]
    #[Assert\NotBlank(message: 'Le nom de la catégorie ne peut pas être vide.')]
    private string $name;

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
        if ($this->name === null) {
            return '';
        }
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

    public function setTabsCat(Collection $tabs_cat): static
    {
        $this->tabs_cat = $tabs_cat;
        return $this;
    }

    public function getTabsCat(): Collection
    {
        return $this->tabs_cat;
    }

    public function addTab(Tabs $tab): static
    {
        if (!$this->tabs_cat->contains($tab)) {
            $this->tabs_cat->add($tab);
        }
        return $this;
    }

    public function removeTab(Tabs $tab): static
    {
        $this->tabs_cat->removeElement($tab);
        return $this;
    }
}