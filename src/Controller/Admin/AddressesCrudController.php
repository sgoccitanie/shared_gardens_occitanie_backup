<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Traits\EasyAdminActionsTrait;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Entity\Addresses;
use App\Entity\Cities;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AddressesCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public function __construct() {}

    public static function getEntityFqcn(): string
    {
        return Addresses::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        // configureCommonActions() doesn't exist in this controller, so configure actions directly
        return $actions
            ->disable(Action::SAVE_AND_CONTINUE);
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

    public function configureFields(string $pageName): iterable
    {
        $fields = [
            TextField::new('street')->setLabel('Rue'),
            NumberField::new('latitude')
                ->setLabel('Latitude')
                ->setNumDecimals(6)
                ->onlyOnIndex()
                ->setHelp('Calculée automatiquement'),
            NumberField::new('longitude')
                ->setLabel('Longitude')
                ->setNumDecimals(6)
                ->onlyOnIndex()
                ->setHelp('Calculée automatiquement'),
        ];

        if ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT) {
            // Champs pour saisir une ville depuis le formulaire
            $fields[] = FormField::addPanel('Ville')
                ->setHelp('Recherchez une ville existante ou créez-en une nouvelle');

            $fields[] = AssociationField::new('city', 'Ville existante')
                ->autocomplete()
                ->setRequired(false)
                ->setCrudController(CitiesCrudController::class)
                ->setHelp('Commencez à taper pour rechercher parmi les villes existantes');

            // Optionnel : garder les champs de création si aucune ville trouvée
            $fields[] = FormField::addPanel('Ou créer une nouvelle ville')
                ->setHelp('Uniquement si la ville n\'existe pas déjà');
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

        $existingCity = $em->getRepository(Cities::class)->findOneBy([
            'name' => $entityInstance->getCityName(),
            'postalcode' => $entityInstance->getCityPostalcode(),
        ]);

        if ($existingCity) {
            $entityInstance->setCity($existingCity);
        } elseif (
            $entityInstance->getCity() === null
            && $entityInstance->getCityName() // vérifie si le formulaire a des données
        ) {
            $city = new Cities();
            $city->setName($entityInstance->getCityName());
            $city->setPostalcode($entityInstance->getCityPostalcode() ?? '');
            $city->setAreaName($entityInstance->getCityAreaName() ?? '');
            $city->setDptName($entityInstance->getCityDptName() ?? '');
            $city->setCountry($em->getReference('App\Entity\Countries', 1));

            $em->persist($city);
            $entityInstance->setCity($city);
        }

        parent::persistEntity($em, $entityInstance);
    }
}
