<?php
// Espace admin : page présentation
namespace App\Controller\Admin;

use App\Entity\Presentation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;

#[IsGranted('ROLE_ADMIN')]
class PresentationCrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public static function getEntityFqcn(): string
    {
        return Presentation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/presentation/form.html.twig']);
    }

    public function index(AdminContext $context): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->redirect(
            $this->container->get(AdminUrlGenerator::class)
                ->setController(self::class)
                ->setAction(Action::EDIT)
                ->setEntityId(1)
            // ->generateUrl()
        );
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            Field::new('content', 'Contenu')
                ->setFormTypeOption('block_name', 'content')
                ->setFormTypeOption('attr', ['class' => 'tinymce'])
                ->setColumns(12),
            // ->hideOnIndex(),
            // Arrivée immédiate sur le formulaire de modification
            /*Field::new('content', 'Extrait')
                ->setTemplatePath('admin/presentation/field_excerpt.html.twig')
                ->onlyOnIndex(),*/
        ];
        
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->configureCommonActions($actions)
            ->disable(Action::SAVE_AND_CONTINUE)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER);
    }
}



