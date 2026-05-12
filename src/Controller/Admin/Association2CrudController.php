<?php

namespace App\Controller\Admin;

use App\Entity\Association;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection as EasyAdminFieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection as EasyAdminFilterCollection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class Association2CrudController extends AbstractCrudController
{

    public function __construct(private ManagerRegistry $doctrine) {}

    public static function getEntityFqcn(): string
    {
        return Association::class;
    }
    // public static function getSubscribedEvents()
    // {
    //     return [
    //         BeforeEntityPersistedEvent::class => ['setAssociation'],
    //     ];
    // }
    // public function setAssociation(BeforeEntityPersistedEvent $event)
    // {
    //     $entity = $event->getEntityInstance();

    //     if (!($entity instanceof Association)) {
    //         return;
    //     }

    //     dump($entity);
    // }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Association')
            ->setEntityLabelInPlural('Associations')
            ->setPageTitle('index', 'Gestion de l\'association')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig'])
        ;
    }
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nom'),
            TextField::new('mantra')->setLabel('Mantra'),
            TextField::new('mobile')->setLabel('Téléphone'),

            ImageField::new('logo')
                ->setLabel('Logo')
                ->setUploadDir('public/uploads/profiles/SDJ/logo/')  // Chemin relatif à 'public/'
                ->setBasePath('/uploads/profiles/SDJ/logo/'),       // Pour l'affichage

            ImageField::new('banner')
                ->setLabel('Bannière')
                ->setUploadDir('public/uploads/profiles/SDJ/banner/')
                ->setBasePath('/uploads/profiles/SDJ/banner/'),
        ];
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $assets
            ->addAssetMapperEntry('app')
            ->addCssFile('styles/admin.css')
            ->addCssFile('styles/form_admin.css')
            ->addHtmlContentToBody('
            <style>
            /***** Bouton "Créer Réseau" *****/
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
                padding:10px 20px !important;
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

    /*
    function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new')
            ->disable('delete')
        ;
    }
        */

    // Supprimer les cases à cocher
    public function configureActions(Actions $actions): Actions
    {

        // Supprimer la ligne avec logo et banner
        $deleteBannerAndLogo = Action::new('deleteBannerAndLogo', 'Supprimer', 'fa fa-trash')
            ->linkToCrudAction('deleteBannerAndLogo')
            ->setCssClass('btn btn-danger')
            ->setHtmlAttributes(['onclick' => 'return confirm("Confirmer la suppression ?");']); // confirmation

        return $actions
            ->add(Crud::PAGE_INDEX, $deleteBannerAndLogo)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)   // retire le DELETE par défaut
            ->disable('batchDelete')
            ->setPermission('batchDelete', 'NO_ACCESS')
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }


    public function deleteBannerAndLogo(AdminContext $context): RedirectResponse
    {
        $association = $context->getEntity()->getInstance();
        $em = $this->doctrine->getManager();

        if ($association) {
            // Supprimer l'entité complètement pour que la ligne disparaisse
            $em->remove($association);
            $em->flush();
        }

        // Rediriger vers la page actuelle de la liste
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }

 
}


