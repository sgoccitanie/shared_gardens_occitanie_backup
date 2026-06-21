<?php

namespace App\Controller\Admin;

use App\Entity\SubjectEmail;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
class SubjectEmailCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public static function getEntityFqcn(): string
    {
        return SubjectEmail::class;
    }
    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Objet')
            ->setEntityLabelInPlural('Objets')
            ->setPageTitle('index', 'Gestion des objets du formulaire de contact')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig'])
        ;
    }


    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets)
            ->addHtmlContentToBody('
            <style>
                .datagrid td, .datagrid th {
                    padding: 12px 50px !important;
                }
            </style>
            ');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable('batchDelete')
            ->setPermission('batchDelete', 'NO_ACCESS')
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('Définition des objets du formulaire de contact'),
            TextField::new('label', 'Titre de l\'objet')->setRequired(true),
        ];
    }
}
