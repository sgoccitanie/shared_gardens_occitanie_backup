<?php
// Dashboard : Gérer des articles
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
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;

#[IsGranted('ROLE_EDITOR')]  // Autoriser les éditeurs ET les admins
class PostsCrudController extends AbstractCrudController
{
    private Security $security;
    private UserRepository $userRepository;

    public function __construct(Security $security, UserRepository $userRepository)
    {
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

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
        return $actions
            ->setPermission('batchDelete', 'NO_ACCESS')
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($this->security->isGranted('ROLE_EDITOR') && !$this->security->isGranted('ROLE_ADMIN')) {
            $user = $this->security->getUser();
            $qb->andWhere('entity.user = :user')
                ->setParameter('user', $user);
        }

        return $qb;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app')
            ->addCssFile('styles/admin.css')
            ->addCssFile('styles/form_admin.css')
            ->addHtmlContentToBody('
            <style>
            /***** Bouton "Créer Article" *****/
            .page-actions .btn,
            .page-actions .btn.btn-primary {
                background-color: #99cd47 !important;
                --bs-btn-bg: #99cd47 !important;
                --bs-btn-hover-bg: #99cd47 !important;
                --bs-btn-active-bg: #99cd47 !important;
                --button-bg: #99cd47 !important;
                --button-primary-bg: #99cd47 !important;
                --button-primary-hover-bg: #99cd47 !important;
                border: none !important;
                padding:10px  !important;
                padding-bottom:26px !important;
                box-shadow : 4px 6px 4px 0 rgba(0, 0, 0, 0.25) !important;
                margin-bottom : 20px !important;
                text-decoration : none !important;
                color:white !important;
            }
            .page-actions .btn:focus,
            .page-actions .btn.btn-primary:focus,
            .page-actions .btn:active,
            .page-actions .btn.btn-primary:active,
            .page-actions .btn:focus-visible,
            .page-actions .btn.btn-primary:focus-visible {
                border: none !important;
                outline: none !important;
            }

            /***** Bouton \'Sauvegarder les modifications\' de la page d\'édition *****/
            .btn.btn-primary,
            .btn.btn-success,
            .btn.btn--light,
            .btn.btn--success,
            .ea-crud-form .form-actions .btn,
            .ea-crud-form .form-actions .btn-primary {
                color: black !important;
            }

            /***** Style pour le tableau  *****/
            .datagrid {
                width: 100%;
                table-layout: auto;
            }
            .datagrid td,
            .datagrid th {
                padding: 12px 15px !important;
                vertical-align: middle !important;
                word-wrap: break-word;
                max-width: 300px;
            }
            .datagrid th {
                font-weight: 600;
                text-transform: uppercase;
                font-size: 0.9rem;
                letter-spacing: 0.5px;
                background-color: #f8f9fa;
            }
            .datagrid tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            </style>
            ');
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
                ->setFormTypeOption('query_builder', function (UserRepository $userRepository) {
                    if ($this->security->isGranted('ROLE_EDITOR') && !$this->security->isGranted('ROLE_ADMIN')) {
                        $user = $this->userRepository->findOneBy(['email' => $this->security->getUser()->getUserIdentifier()]);
                        return $userRepository->createQueryBuilder('u')
                            ->where('u.id = :userId')
                            ->setParameter('userId', $user->getId());
                    }
                    return $userRepository->createQueryBuilder('u');
                }),
            Field::new('status', 'En ligne ?'),
            AssociationField::new('categories', 'Catégories')->setFormTypeOption('choice_label', 'name')
                ->setFormTypeOption('by_reference', false),
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
        $content = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $content);   // Paragraphes vides
        $content = preg_replace('/<!--[^\[>](.*?)-->/', '', $content); // Commentaires HTML

        return $content;
    }

    /* Nettoyer le contenu avant de l'envoyer au template */
    public function editEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Posts $entityInstance */
        $content = $entityInstance->getContent();
        if (!empty($content)) {
            $content = $this->cleanContent($content);
            $entityInstance->setContent($content);
        }
    }
}
