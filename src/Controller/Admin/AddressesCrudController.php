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
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;

class AddressesCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

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
        return $this->configureCommonAssets($assets, '5px 30px')
            ->addHtmlContentToBody('
            <style>
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
                max-width: 200px;
                white-space: normal;
                word-wrap: break-word;
            }
            </style>
            ');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->configureCommonActions($actions)
            ->disable(Action::SAVE_AND_CONTINUE);
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
