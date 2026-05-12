<?php

namespace App\Controller\Admin;

use App\Entity\Keywords;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]  // Autoriser les éditeurs ET les admins
class KeywordsCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Keywords::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Keyword')
            ->setEntityLabelInPlural('Keywords')
            ->setPageTitle('index', 'Gestion des keywords')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig'])
        ;
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
            
            /***** Style pour le tableau  *****/
            .datagrid {
                width: 100%;
                table-layout: auto;
            }
            .datagrid td,
            .datagrid th {
                padding: 12px 50px !important;
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

    /* Temporaire : BOUTON ajouter un mot clé : caché
    function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('new');
    }
            */

    // Supprimer les cases à cocher
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->disable('batchDelete')
            ->setPermission('batchDelete', 'NO_ACCESS')
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('label')->setRequired(true),
        ];
    }
}
