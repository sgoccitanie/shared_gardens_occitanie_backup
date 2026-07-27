<?php
// Modérer un commenaire

namespace App\Controller\Admin;

use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Text;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Google\Service\Spanner\Field;
use Psr\Log\LoggerInterface;

#[IsGranted('ROLE_EDITOR')]
class CommentCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;


    public function __construct(
        private readonly LoggerInterface $logger,
    )
    {}

    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    // public function createEntity(string $entityFqcn)
    // {
    //     $comment = new Comment();
    //     return $comment;
    // }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commentaire')
            ->setEntityLabelInPlural('Commentaires')
            ->setPageTitle('index', 'Liste des %entity_label_plural%')
            ->setPageTitle('detail', fn(Comment $comment) => (string) $comment)
            ->setPageTitle('edit', fn(Comment $comment) => sprintf('Modifier le commentaire n°%d', $comment->getId()))
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig'])
        ;
    }

    // Supprimer les boutons inutiles
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_EDIT, Action::INDEX)
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, function (Action $action) {
                return $action->setLabel('Enregistrer et rester');
            })
            ->update(Crud::PAGE_EDIT, Action::INDEX, function (Action $action) {
                return $action
                    ->setLabel('Retour aux commentaires')
                    ->setIcon('fa fa-arrow-left');
            })
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    // Style des boutons
    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets)
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

    private function cleanContent(?string $content): string
    {
        if (empty($content)) {
            return '';
        }

        $content = preg_replace('/<p[^>]*>\s*<\/p>/i', '', $content);
        $content = preg_replace('/<!--(?!\[if).*?-->/s', '', $content);

        // return $this->sanitizer->sanitize($content);
        return $content;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            $content = $entityInstance->getContent();
            $pseudo = $entityInstance->getPseudo();
            // Si content n'est pas vide on le clean et on set le contenu dans Comment avec la méthode setContent
            if (!empty($content)) {
                $entityInstance->setContent($this->cleanContent($content));
            }
            if ($entityInstance->getParent() !== null && empty($entityInstance->getPseudo())) {
                $entityInstance->setPseudo('Réseau des Semeurs de Jardins');
            }

            parent::updateEntity($entityManager, $entityInstance);
            $this->addFlash('success', 'Le commentaire a été modifié avec succès.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur modification commentaire : ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('danger', "Le commentaire n'a pas pu être modifié.");
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            $content = $entityInstance->getContent();
            $pseudo = $entityInstance->getPseudo();

            if (!empty($content)) {
                $content = $this->cleanContent($content);
                $entityInstance->setContent($content);
            }
            if(!empty($pseudo)){
                $pseudo = $this->cleanContent($pseudo);
                $entityInstance->setPseudo($pseudo);
            }
            // Si le commentaire a un parent et est vide
            if ($entityInstance->getParent() !== null && empty($entityInstance->getPseudo())) {
                $entityInstance->setPseudo('Réseau des Semeurs de Jardins');
            }

            parent::persistEntity($entityManager, $entityInstance);
            $this->addFlash(
                'success',
                'Le commentaire a été ajouté avec succès =)'
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur création commentaire : ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->addFlash('danger', "Le commentaire n'a pas pu être enregistré =/ Réessayez ou contactez l'administrateur.");

        }
    }

     public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Comment $entityInstance */
        $content = $entityInstance->getContent();

        try {
            parent::deleteEntity($entityManager, $entityInstance);
            $extrait = mb_substr($content, 0, 10);
            $this->addFlash('success', sprintf('Le commentaire « %s… » a bien été supprimé.', $extrait));
        } catch (\Exception $e) {
            $this->logger->error('Erreur suppression article : ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('danger', "Le commentaire n'a pas pu être supprimé. Il est peut-être lié à d'autres contenus.");
        }
    }

    public function configureFields(string $pageName): iterable
    {
        yield AssociationField::new('post', 'Article associé');

        yield AssociationField::new('parent', 'En réponse à')
            ->setHelp('Laisser vide pour un commentaire principal, ou choisir le commentaire auquel celui-ci répond')
            ->setFormTypeOption('choice_label', fn(Comment $c) => sprintf(
                '#%d — %s : %.40s',
                $c->getId(),
                $c->getPseudo() ?? 'Anonyme',
                $c->getContent()
            ))
            ->setFormTypeOption('required', false)
             ->formatValue(function ($value, $entity) {
                $parent = $entity->getParent();
                if ($parent === null) {
                    return '—';
                }
                // mb_substr permet de récupérer les 50 premieres chaines de caractères ici (0, 50) 
                $extrait = mb_substr($parent->getContent(), 0, 50);
                // sprintf permet de créer un format à partir ici du pseudo et de l'extrait
                return sprintf('%s : « %s… »', $parent->getPseudo() ?? 'Anonyme', $extrait);
            });

        yield TextareaField::new('content', 'Contenu');

        yield AssociationField::new('user', 'Utilisateur')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Date de création')->setDisabled(true);

        yield BooleanField::new('isApproved', 'Approuvé')->renderAsSwitch(true);
    }
}
