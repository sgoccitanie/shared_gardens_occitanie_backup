<?php
// Dashboard : Les commentaires
namespace App\Controller\Admin;

use App\Entity\Comment;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;

#[IsGranted('ROLE_EDITOR')] // Réservé aux éditeurs et admins
class CommentCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function createEntity(string $entityFqcn)
    {
        $comment = new Comment();
        // $comment->setCreatedAt(new \DateTimeImmutable());  // $createdAt est initialisée dans le constructeur
        return $comment;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commentaire')
            ->setEntityLabelInPlural('Commentaires')
            ->setPageTitle('index', 'Liste des %entity_label_plural%')
            ->setPageTitle('detail', fn(Comment $comment) => (string) $comment)
            ->setPageTitle('edit', fn(Comment $comment) => sprintf('Modifier le commentaire n°%d', $comment->getId()))
            // Supprimer ou commenter la ligne suivante si pas besoin de template personnalisé
            // ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/comments/form.html.twig'])
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig'])
        ;
    }

    // Supprimer les boutons inutiles
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER); 
    }

    // Style des boutons
    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets, '10px', '#729D2D')
            ->addHtmlContentToBody('
            <style>
                .page-actions .btn:focus,
                .page-actions .btn.btn-primary:focus,
                .page-actions .btn:active,
                .page-actions .btn.btn-primary:active,
                .page-actions .btn:focus-visible,
                .page-actions .btn.btn-primary:focus-visible {
                    background-color: #729D2D !important;
                    color: white !important;
                }
            </style>
            ');
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $qb = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        return $qb;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('post', 'Article'),
            AssociationField::new('user', 'Utilisateur')
                ->setFormTypeOption('query_builder', function (UserRepository $userRepository) {
               
                    return $userRepository->createQueryBuilder('u');
                }),
            TextareaField::new('content', 'Contenu'),
            DateTimeField::new('createdAt', 'Date de création')->setDisabled(true),
        ];
    }
}
