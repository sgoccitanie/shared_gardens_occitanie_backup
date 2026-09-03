<?php

namespace App\Controller\Admin;

use App\Entity\Categories;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;
use App\Entity\Tabs;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
class CategoriesCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public function __construct( private readonly LoggerInterface $logger) {}

    public static function getEntityFqcn(): string
    {
        return Categories::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')            
            ->setDefaultSort(['id' => 'DESC'])
            ->setPageTitle('index', 'Gestion des catégories')
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig'])
        ;
    }
    public function createIndexQueryBuilder(
        SearchDto $searchDto, 
        EntityDto $entityDto, 
        FieldCollection $fields, 
        FilterCollection $filters
    ): QueryBuilder {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $queryBuilder->andWhere('entity.id != :id')
                    ->setParameter('id', 2);

        return $queryBuilder;
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {

        try {

            /** @var Categories $entityInstance */
            // La relation Categories-Tabs est ManyToMany et est portée par Tabs.
            $TabsInDb = $entityManager->getRepository(Tabs::class)->createQueryBuilder('tab')
                ->innerJoin('tab.categories', 'category')
                ->andWhere('category = :category')
                ->setParameter('category', $entityInstance)
                ->getQuery()
                ->getResult();
            $TabsInForm = $entityInstance->getTabsCat()->toArray();

            // Détacher les tabs qui étaient en base mais plus dans le formulaire
            foreach ($TabsInDb as $tab) {
                if (!in_array($tab, $TabsInForm, true)) {
                    $tab->removeCategory($entityInstance);
                    $entityManager->persist($tab);
                }
            }

            // Attacher les tabs nouveaux
            foreach ($TabsInForm as $tab) {
                $tab->addCategory($entityInstance);
                $entityManager->persist($tab);
            }

            parent::updateEntity($entityManager, $entityInstance);
            $this->addFlash('success', 'La catégorie a été modifiée avec succès.');
        } catch (\Exception $e) {
            $this->logger->error('Erreur modification catégorie : ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('danger', "La catégorie n'a pas pu être modifiée.");
        }
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        try {
            /** @var Categories $entityInstance */
            $TabsInForm = $entityInstance->getTabsCat()->toArray();

            foreach ($TabsInForm as $tab) {
                $tab->addCategory($entityInstance);
                $entityManager->persist($tab);
            }
            parent::persistEntity($entityManager, $entityInstance);
            $this->addFlash(
                'success',
                'La catégorie a été ajoutée avec succès =)'
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur création catégorie : ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->addFlash('danger', "La catégorie n'a pas pu être enregistrée =/ Réessayez ou contactez l'administrateur.");

        }
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Categories $entityInstance */
        $name = $entityInstance->getName();

        foreach ($entityInstance->getTabsCat() as $tab) {
            $tab->removeCategory($entityInstance);
            $entityManager->persist($tab);
        }

        try {
            parent::deleteEntity($entityManager, $entityInstance);
            $this->addFlash('success', sprintf('La catégorie « %s » a bien été supprimée.', $name));
        } catch (\Exception $e) {
            $this->logger->error('Erreur suppression catégorie : ' . $e->getMessage(), ['exception' => $e]);
            $this->addFlash('danger', "La catégorie n'a pas pu être supprimée. Il est peut-être liée à d'autres contenus.");
        }
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
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->disable('batchDelete')
            ->setPermission('batchDelete', 'NO_ACCESS')->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Nom de la catégorie'),
            AssociationField::new('tabs_cat', 'Pages liées')
                ->setFormTypeOption('by_reference', false)
                ->setFormTypeOption('choice_label', 'label')
                ->setFormTypeOption('multiple', true)
                ->setFormTypeOption('choice_label', 'label')
                ->setHelp('Une catégorie peut être rattachée à plusieurs pages')
        ];
    }
}
