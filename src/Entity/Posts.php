<?php

namespace App\Entity;

use App\Repository\PostsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: PostsRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug existe déjà')]
class Posts
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre ne peut pas être vide')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins 3 caractères', maxMessage: 'Le titre ne peut pas contenir plus de 255 caractères')]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le contenu ne peut pas être vide')]
    #[Assert\Length(min: 10, minMessage: 'Le contenu doit contenir au moins 10 caractères')]
    private ?string $content = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'Le slug ne peut pas être vide')]
    #[Assert\Length(min: 3, max: 300, minMessage: 'Le slug doit contenir au moins 3 caractères', maxMessage: 'Le slug ne peut pas contenir plus de 300 caractères')]
    private ?string $slug = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $posted_at = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $modified_at = null;

    #[ORM\Column(nullable: true)]
    private ?int $likes_counter = null;

    #[ORM\Column]
    private ?bool $status = null;

    #[ORM\Column(nullable: true)]
    private ?int $comment_counter = null;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', cascade: ['remove'], orphanRemoval: true)]
    private Collection $comments;

    /**
     * @var Collection<int, Keywords>
     */
    #[ORM\ManyToMany(targetEntity: Keywords::class, inversedBy: 'keywords_posts')]
    private Collection $keywords;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank(message: 'Veuillez indiquer le créateur du post.')]
    private ?User $user = null;
    /**
     * @var Collection<int, Tabs>
     */
    #[ORM\ManyToMany(targetEntity: Tabs::class, inversedBy: 'tabs_posts')]
    private Collection $tabs;

    /**
     * @var Collection<int, Files>
     */
    #[ORM\ManyToMany(targetEntity: Files::class, inversedBy: 'posts')]
    private Collection $files;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $path = null;

    /**
     * @var Collection<int, Postmeta>
     */
    #[ORM\OneToMany(mappedBy: "post", targetEntity: Postmeta::class, cascade: ["persist", "remove"], orphanRemoval: true)]
    private Collection $metas;

    public function __construct()
    {
        $this->keywords = new ArrayCollection();
        $this->tabs = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->metas = new ArrayCollection();
        $this->comments = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->slug;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): static
    {
        // Décoder une première fois
        $content = html_entity_decode($content ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Décoder une deuxième fois (cas où le contenu est double-encodé)
        $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $this->content = $content;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): static
    {

        $this->slug = $slug ? mb_strtolower($slug) : null;

        return $this;
    }

    public function getPostedAt(): ?\DateTimeImmutable
    {
        return $this->posted_at;
    }

    public function setPostedAt(\DateTimeImmutable $posted_at): static
    {
        $this->posted_at = $posted_at;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modified_at;
    }

    public function setModifiedAt(?\DateTimeImmutable $modified_at): static
    {
        $this->modified_at = $modified_at;

        return $this;
    }

    public function getLikesCounter(): ?int
    {
        return $this->likes_counter;
    }

    public function setLikesCounter(?int $likes_counter): static
    {
        $this->likes_counter = $likes_counter;

        return $this;
    }

    public function isStatus(): ?bool
    {
        return $this->status;
    }

    public function setStatus(bool $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCommentCounter(): ?int
    {
        return $this->comment_counter;
    }

    public function setCommentCounter(?int $comment_counter): static
    {
        $this->comment_counter = $comment_counter;

        return $this;
    }
    /**
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Tabs>
     */
    public function getTabs(): Collection
    {
        return $this->tabs;
    }

    public function setTabs(Collection $tabs): static
    {
        $this->tabs = $tabs;
        return $this;
    }

    public function addTab(Tabs $tab): static
    {
        if (!$this->tabs->contains($tab)) {
            $this->tabs->add($tab);
        }
        return $this;
    }

    public function removeTab(Tabs $tab): static
    {
        $this->tabs->removeElement($tab);
        return $this;
    }
    /**
     * @return Collection<int, Keywords>
     */
    public function getKeywords(): Collection
    {
        return $this->keywords;
    }

    public function addKeyword(Keywords $keyword): static
    {
        if (!$this->keywords->contains($keyword)) {
            $this->keywords->add($keyword);
        }

        return $this;
    }

    public function removeKeyword(Keywords $keyword): static
    {
        $this->keywords->removeElement($keyword);

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Files>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(Files $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
        }

        return $this;
    }

    public function removeFile(Files $file): static
    {
        $this->files->removeElement($file);

        return $this;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path): static
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return Collection<int, Postmeta>
     */
    public function getMetas(): Collection
    {
        return $this->metas;
    }

    public function addMeta(Postmeta $meta): static
    {
        if (!$this->metas->contains($meta)) {
            $this->metas->add($meta);
            $meta->setPost($this);
        }

        return $this;
    }

    public function removeMeta(Postmeta $meta): static
    {
        if ($this->metas->removeElement($meta)) {
            if ($meta->getPost() === $this) {
                $meta->setPost(null);
            }
        }

        return $this;
    }
}
