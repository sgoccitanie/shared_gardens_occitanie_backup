<?php

namespace App\Entity;

use App\Repository\TabsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TabsRepository::class)]
#[UniqueEntity(fields: ['slug'])]
class Tabs
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 60)]
    private ?string $label = null;

    #[ORM\Column]
    private ?bool $news_feed = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $slug = null;

    #[ORM\ManyToMany(targetEntity: Categories::class, inversedBy: 'tabs_cat')]
    private Collection $categories;

    /**
     * @var Collection<int, Posts>
     */
    #[ORM\ManyToMany(targetEntity: Posts::class, mappedBy: 'tabs')]
    private Collection $tabs_posts;


    public function __construct()
    {
        $this->tabs_posts = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->news_feed = false;
    }

    public function __toString(): string
    {
        return $this->label ?? '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function isNewsFeed(): ?bool
    {
        return $this->news_feed;
    }

    public function setNewsFeed(bool $news_feed): static
    {
        $this->news_feed = $news_feed;
        return $this;
    }

    public function getTabsPosts(): Collection
    {
        return $this->tabs_posts;
    }

    public function setTabsPosts(Collection $tabs_posts): static
    {
        $this->tabs_posts = $tabs_posts;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug ? mb_strtolower($slug) : null;
        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function setCategories(Collection $categories    ): static
    {
        $this->categories = $categories;
        return $this;
    }

    
    
    public function addTabsPost(Posts $tabsPost): static
    {
        if (!$this->tabs_posts->contains($tabsPost)) {
            $this->tabs_posts->add($tabsPost);
            $tabsPost->addTab($this);
        }
        return $this;
    }

    public function removeTabsPost(Posts $tabsPost): static
    {
        if ($this->tabs_posts->removeElement($tabsPost)) {
            $tabsPost->removeTab($this);
        }
        return $this;
    }

    
    
    public function addCategory(Categories $categories): static
    {
        if (!$this->categories->contains($categories)) {
            $this->categories->add($categories);
            $categories->addTabsCat($this);
        }
        return $this;
    }

    public function removeCategory(Categories $categories): static
    {
        if ($this->categories->removeElement($categories)) {
            $categories->removeTabsCat($this);
        }
        return $this;
    }
}