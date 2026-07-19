<?php

namespace App\Controller\Admin;

use App\Entity\Tabs;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;
use Doctrine\ORM\EntityManagerInterface;

#[IsGranted('ROLE_EDITOR')]
class TabsCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public static function getEntityFqcn(): string
    {
        return Tabs::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Page')
            ->setEntityLabelInPlural('Pages')
            ->setPageTitle('index', 'Listes des %entity_label_plural%')
            ->setPageTitle('detail', fn(Tabs $tab) => (string) $tab)
            ->setPageTitle('edit', fn(Tabs $tab) => sprintf('Edition de "<b>%s</b>"', $tab->getLabel()))
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig'])
        ;
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets);
    }

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
            TextField::new('label', 'Titre de la page'),
            SlugField::new('slug')->setTargetFieldName(['label'])->setFormTypeOption('attr', ['readonly' => true])->setUnlockConfirmationMessage(
                'Il est recommandé d\'utiliser les slugs automatiques, mais vous pouvez les personnaliser'
            ),
            AssociationField::new('category')->setLabel('Catégorie associée'),
            AssociationField::new('tabs_posts')->setLabel('Article(s) associé(s)'),
            AssociationField::new('pages')->setLabel('Nom de l\'onglet')
        ];
    }

    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Tabs $entityInstance */

        // Détacher tous les posts liés à cette tab (ils restent en BDD)
        foreach ($entityInstance->getTabsPosts() as $post) {
            $post->setTab(null);
            $entityManager->persist($post);
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }
}
