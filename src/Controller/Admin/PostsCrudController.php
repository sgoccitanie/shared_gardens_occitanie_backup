<?php

namespace App\Controller\Admin;

use App\Entity\Posts;
use App\Repository\CategoriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;

#[IsGranted('ROLE_EDITOR')]
class PostsCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Posts::class;
    }

    public function createEntity(string $entityFqcn)
    {
        $post = new Posts();
        $post->setPostedAt(new \DateTimeImmutable());
        return $post;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Article')
            ->setEntityLabelInPlural('Articles')
            ->setPageTitle('index', 'Liste des %entity_label_plural%')
            ->setPageTitle('detail', fn(Posts $post) => (string) $post)
            ->setPageTitle('edit', fn(Posts $post) => sprintf('Édition de "<b>%s</b>"', $post->getSlug()))
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->configureCommonActions($actions);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($this->security->isGranted('ROLE_EDITOR') && !$this->security->isGranted('ROLE_ADMIN')) {
            $user = $this->security->getUser();
            if ($user !== null) {
                $qb->andWhere('entity.user = :user')
                    ->setParameter('user', $user);
            }
        }

        return $qb;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addColumn(10),
            TextField::new('title', 'Titre de l\'article'),
            DateField::new('postedAt')->setFormat('short')->setDisabled(true),
            FormField::addColumn(2),
            SlugField::new('slug')->setTargetFieldName(['title', 'postedAt'])->setFormTypeOption('attr', ['readonly' => true])->setUnlockConfirmationMessage(
                'Il est recommandé d\'utiliser les slugs automatiques, mais vous pouvez les personnaliser'
            ),
            AssociationField::new('user', 'Auteur')
                ->setFormTypeOption('query_builder', function () {
                    if ($this->security->isGranted('ROLE_EDITOR') && !$this->security->isGranted('ROLE_ADMIN')) {
                        $user = $this->userRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);
                        return $this->userRepository->createQueryBuilder('u')
                            ->where('u.id = :userId')
                            ->setParameter('userId', $user->getId());
                    }
                    return $this->userRepository->createQueryBuilder('u');
                }),
            Field::new('status', 'En ligne ?'),
            AssociationField::new('categories', 'Catégories')
                ->setFormTypeOption('choice_label', 'name')
                ->setFormTypeOption('by_reference', false)
                ->setFormTypeOption('query_builder', function (CategoriesRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->where('c.id != :id')
                        ->setParameter('id', 2) // Assuming 2 is the ID of the 'À venir' category
                        ->orderBy('c.name', 'ASC');
                }),
            AssociationField::new('tab', 'Pages')->setFormTypeOption('choice_label', 'label'),
            AssociationField::new('keywords', 'Mots clés')->setFormTypeOption('choice_label', 'label'),

            // Formulaire pour créer un article avec TinyMCE (templates\admin\posts\form.html.twig)
            TextareaField::new('content', 'Contenu')
                ->hideOnIndex()
                ->setFormTypeOptions([
                    'block_name' => 'content',
                ])
                ->setFormTypeOption('attr', ['class' => 'tinymce']),
        ];
    }

    /**
     * Nettoyer le contenu HTML pour éviter les balises parasites
     * Attention : ne doit pas supprimer les iframes (sinon les pdf ne pourront pas s'afficher !)
     * NB : TinyMCE gère déjà les divs vides
     */
    private function cleanContent(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        // Supprimer UNIQUEMENT les paragraphes vides et les commentaires HTML => TinyMCE gère les div 
        $content = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $content);
        // Supprime tous les commentaires sauf les conditionnels IE (au cas où)
        $content = preg_replace('/<!--(?!\[if).*?-->/s', '', $content);

        return $content;
    }

    /* Nettoyer le contenu avant de l'envoyer au template */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Posts $entityInstance */
        $content = $entityInstance->getContent();
        if (!empty($content)) {
            $content = $this->cleanContent($content);
            $entityInstance->setContent($content);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Posts $entityInstance */
        $content = $entityInstance->getContent();
        if (!empty($content)) {
            $content = $this->cleanContent($content);
            $entityInstance->setContent($content);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }
}
