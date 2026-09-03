<?php

namespace App\Controller\Admin;

use App\Entity\Posts;
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
use App\Entity\Categories;
use App\Entity\Tabs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_EDITOR')]
class PostsCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public function __construct(
        private readonly Security $security,
        private readonly UserRepository $userRepository,
        private readonly LoggerInterface $logger
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
            ->setDefaultSort(['id' => 'DESC'])
            ->setPageTitle('edit', fn(Posts $post) => sprintf('Édition de l\'article "<b>%s</b>"', $post->getTitle()))
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // Modifier "Enregistrer et continuer d'éditer"
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action->setLabel('Enregistrer et rester');
            })
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->add(Crud::PAGE_NEW, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::INDEX, function (Action $action) {
                return $action
                    ->setLabel('Retour aux articles')
                    ->setIcon('fa fa-arrow-left');
            })
            ->update(Crud::PAGE_NEW, Action::INDEX, function (Action $action) {
                return $action
                    ->setLabel('Retour aux articles')
                    ->setIcon('fa fa-arrow-left');
            });
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
            SlugField::new('slug')
                ->setTargetFieldName(['title'])
                ->setFormTypeOption('attr', ['readonly' => true])
                ->setUnlockConfirmationMessage(
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
            AssociationField::new('tabs', 'Pages')
                ->setFormTypeOption('by_reference', false)
                ->setFormTypeOption('multiple', true)
                ->setFormTypeOption('choice_label', function (Tabs $tab): string {
                    $categories = $tab->getCategories();

                    if ($categories->isEmpty()) {
                        return $tab->getLabel() . ' — (aucune catégorie)';
                    }

                    $names = array_map(
                        fn(Categories $c) => $c->getName(),
                        $categories->toArray()
                    );

                    return sprintf('%s — %s', $tab->getLabel(), implode(', ', $names));
                }),
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

        $content = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $content);
        $content = preg_replace('/<!--(?!\[if).*?-->/s', '', $content);
        // Déballe les wrappers WordPress (garde le contenu, retire le div englobant)
        $content = preg_replace('#<div[^>]*(id="(container|content)"|class="[^"]*(hentry|entry-content|post-\d+)[^"]*")[^>]*>#i', '', $content);

        // Retire les attributs data-* de WordPress
        $content = preg_replace('/\s*data-[a-z-]+="[^"]*"/i', '', $content);

        // Retire les paddings inline parasites
        $content = preg_replace('/\s*padding-left:\s*\d+px;?/i', '', $content);

        $content = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $content);


        // return $this->sanitizer->sanitize($content);
        return $content;
    }

    private function ensureUniqueSlug(EntityManagerInterface $entityManager, Posts $post): void
    {
        $baseSlug = $post->getSlug();
        if (empty($baseSlug)) {
            return;
        }

        $slug = $baseSlug;
        $counter = 1;

        while (true) {
            $existing = $entityManager->getRepository(Posts::class)->findOneBy(['slug' => $slug]);

            // Si aucun autre post avec ce slug (ou c'est le post actuel), on garde
            if ($existing === null || $existing->getId() === $post->getId()) {
                $post->setSlug($slug);
                return;
            }

            // Sinon, ajoute un suffixe
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
    }

    /* Nettoyer le contenu avant de l'envoyer au template */
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Posts $entityInstance */
        $entityInstance->setPostedAt(new \DateTimeImmutable());
        try {
            $content = $entityInstance->getContent();
            if (!empty($content)) {
                $content = $this->cleanContent($content);
                $entityInstance->setContent($content);
            }
            // Assure l'unicité du slug
            $this->ensureUniqueSlug($entityManager, $entityInstance);

            parent::updateEntity($entityManager, $entityInstance);
            $this->addFlash(
                'success',
                'L\'article a été modifié avec succès.'
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur modification article : ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->addFlash('danger', "Les modifications de l'article n'ont pas pu être enregistrées. Réessayez ou contactez l'administrateur.");
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Posts $entityInstance */
        try {
            $content = $entityInstance->getContent();
            if (!empty($content)) {
                $content = $this->cleanContent($content);
                $entityInstance->setContent($content);
            }
            // Assure l'unicité du slug
            $this->ensureUniqueSlug($entityManager, $entityInstance);

            parent::persistEntity($entityManager, $entityInstance);
            $this->addFlash(
                'success',
                'L\'article a été ajouté avec succès.'
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur création article : ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->addFlash('danger', "L'article n'a pas pu être enregistré. Réessayez ou contactez l'administrateur.");

        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Posts $entityInstance */
        $title = $entityInstance->getTitle();

        try {
            parent::deleteEntity($entityManager, $entityInstance);
            $this->addFlash('success', sprintf('L\'article "%s" a bien été supprimé.', $title));
        } catch (\Exception $e) {
            $this->logger->error('Erreur suppression article : ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('danger', "L'article n'a pas pu être supprimé. Il est peut-être lié à d'autres contenus.");
        }
    }
}
