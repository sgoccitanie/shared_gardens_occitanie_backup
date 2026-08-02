<?php

namespace App\Controller\Admin;

use App\Entity\Association;
use App\Entity\Addresses;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AssociationCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public function __construct(
        private ManagerRegistry $doctrine
    ) {}

    public static function getEntityFqcn(): string
    {
        return Association::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Association')
            ->setEntityLabelInPlural('Associations')
            ->setPageTitle('index', 'Gestion de l\'association')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig']);
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
            return $action->setLabel('Créer'); // Modifier bouton "Créer Réseau" par "Créer"
        });

        return $actions
            ->disable('batchDelete')
            ->setPermission('batchDelete', 'NO_ACCESS')
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    public function configureFields(string $pageName): iterable
    {
        $this->checkMissingAddresses();

        return [
            FormField::addPanel('Informations générales')->setIcon('fa fa-user')->addCssClass('required'),
            TextField::new('name')->setLabel('Nom'),
            TextField::new('acronyme')->setLabel('Acronyme'),
            TextField::new('mantra')->setLabel('Mantra'),
            TextField::new('description')->setLabel('Description'),

            FormField::addPanel('Informations de contact')->setIcon('fa fa-mobile')->addCssClass('required'),
            TextField::new('email')->setLabel('Email'),
            TextField::new('mobile')->setLabel('Téléphone'),

            FormField::addPanel('Coordonnées')->setIcon('fa fa-map-marker'),
            AssociationField::new('address')
                ->setLabel('Adresse')
                ->setRequired(false)
                ->autocomplete()
        ];
    }

    private function checkMissingAddresses(): void
    {
        $em = $this->doctrine->getManager();

        $addressRepo = $em->getRepository(Addresses::class);
        $allAddressIds = array_map(fn($a) => $a->getId(), $addressRepo->findAll());

        $assocRepo = $em->getRepository(Association::class);
        $allAssociations = $assocRepo->findAll();

        foreach ($allAssociations as $assoc) {
            $address = $assoc->getAddress();
            if ($address !== null && !in_array($address->getId(), $allAddressIds, true)) {
                // Supprimer l'adresse invalide pour éviter l'erreur
                $assoc->setAddress(null);
            }
        }
    }
}
