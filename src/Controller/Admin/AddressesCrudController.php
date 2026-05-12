<?php

namespace App\Controller\Admin;


use App\Entity\Addresses;
use App\Entity\Cities;
use App\Entity\Countries;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Doctrine\ORM\EntityManagerInterface;

class AddressesCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Addresses::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud

            // the labels used to refer to this entity in titles, buttons, etc.
            ->setEntityLabelInSingular('Adresse')
            ->setEntityLabelInPlural('Adresses')
            ->setPageTitle('index', 'Gestion des adresses')
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
                padding:5px 30px !important;
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

            /***** Tableau responsive *****/
            .datagrid-wrapper {
                overflow-x: auto;
            }
            .datagrid {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            .datagrid td,
            .datagrid th {
                max-width: 200px; /* ajuster si besoin */
                white-space: normal;
                word-wrap: break-word;
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

    // Supprimer les cases à cocher
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::SAVE_AND_CONTINUE)
            ->disable('batchDelete')
            ->setPermission('batchDelete', 'NO_ACCESS')
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('street')->setLabel('Rue'),
            TextField::new('longitude')->setLabel('Longitude'),
            TextField::new('latitude')->setLabel('Latitude'),
        ];

        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            // Champs pour saisir une ville depuis le formulaire
            $fields[] = FormField::addPanel('Ville')->setHelp('Renseignez les informations de la ville');
            $fields[] = TextField::new('cityName', 'Nom de la ville');
            $fields[] = TextField::new('cityPostalcode', 'Code postal');
            $fields[] = TextField::new('cityAreaName', 'Région');
            $fields[] = TextField::new('cityDptName', 'Département');
        } else {
            // PAGE INDEX / DETAIL : afficher la ville liée
            $fields[] = TextField::new('city.name', 'Nom de la ville');
            $fields[] = TextField::new('city.postalcode', 'Code postal');
            $fields[] = TextField::new('city.areaName', 'Région');
            $fields[] = TextField::new('city.dptName', 'Département');
        }

        return $fields;
    }

    public function persistEntity(EntityManagerInterface $em, $entityInstance): void
    {
        if (!$entityInstance instanceof Addresses) return;

        if (
            $entityInstance->getCity() === null
            && $entityInstance->getCityName() // vérifie si le formulaire a des données
        ) {
            $city = new Cities();
            $city->setName($entityInstance->getCityName());
            $city->setPostalcode($entityInstance->getCityPostalcode() ?? '');
            $city->setAreaName($entityInstance->getCityAreaName() ?? '');
            $city->setDptName($entityInstance->getCityDptName() ?? '');
            $city->setCountry($em->getReference('App\Entity\Countries', 1)); // par défaut

            $em->persist($city);
            $entityInstance->setCity($city);
        }

        parent::persistEntity($em, $entityInstance);
    }
}
