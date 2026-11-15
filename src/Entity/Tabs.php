<?php

namespace App\Entity;

use App\Repository\TabsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
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

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $content = null;

    /**
     * true  : la page affiche son propre contenu, suivi de ses articles associés (carrousel)
     * false : la page est une page d'articles (liste, ou article seul s'il n'y en a qu'un)
     */
    #[ORM\Column(options: ['default' => false])]
    private bool $own_content = false;

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

    public function setCategories(Collection $categories): static
    {
        $this->categories = $categories;
        return $this;
    }
    public function isOwnContent(): bool
    {
        return $this->own_content;
    }

    public function setOwnContent(bool $own_content): static
    {
        $this->own_content = $own_content;
        return $this;
    }

    /**
     * Contenu à afficher sur le site : seulement si la page est en mode « contenu propre ».
     * Désactiver l'option ne supprime pas le contenu saisi, il est simplement ignoré.
     */
    public function getDisplayedContent(): ?string
    {
        return $this->own_content ? $this->getContent() : null;
    }

    public function getContent(): ?string
    {
        // Texte brut (collé sans mise en forme, aucune balise) : on reconstitue les paragraphes
        // pour qu'ils s'affichent dans l'éditeur comme sur le site. Le HTML est renvoyé tel quel.
        if ($this->content !== null && $this->content === strip_tags($this->content)) {
            $paragraphs = preg_split('/\R\s*\R/u', trim($this->content)) ?: [];
            // Retire aussi les espaces insécables en début/fin de paragraphe
            $paragraphs = array_map(fn (string $p) => preg_replace('/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $p) ?? $p, $paragraphs);
            $paragraphs = array_filter($paragraphs, fn (string $p) => $p !== '');

            return implode("\n", array_map(fn (string $p) => '<p>' . nl2br($p, false) . '</p>', $paragraphs));
        }

        return $this->content;
    }

    public function setContent(?string $content): static
    {
        // Contenu facultatif : une page peut n'afficher que ses articles
        if ($content === null || trim($content) === '') {
            $this->content = null;
            return $this;
        }

        // Décoder une première fois
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Décoder une deuxième fois (cas où le contenu est double-encodé)
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->content = $content;
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

    public function addCategory(Categories $tabsCat): static
    {
        if (!$this->categories->contains($tabsCat)) {
            $this->categories->add($tabsCat);
            $tabsCat->addTab($this);
        }
        return $this;
    }

    public function removeCategory(Categories $tabsCat): static
    {
        if ($this->categories->removeElement($tabsCat)) {
            $this->categories->removeElement($tabsCat);
            $tabsCat->removeTab($this);
        }
        return $this;
    }
    
   
}