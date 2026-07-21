<?php
// Espace admin : page présentation
namespace App\Controller\Admin;

use App\Entity\Presentation;
use App\Repository\PresentationRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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

    private PresentationRepository $presentationRepo;

    public function __construct(PresentationRepository $presentationRepo)
    {
        $this->presentationRepo = $presentationRepo;
    }

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

        $presentation = $this->presentationRepo->find(1);

        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);

        if ($presentation) {
            return $this->redirect(
                $adminUrlGenerator
                    ->setController(self::class)
                    ->setAction(Action::EDIT)
                    ->setEntityId($presentation->getId())
            );
        }
        // Si aucune présentation n'existe créer une nouvelle
        return $this->redirect(
            $adminUrlGenerator
                ->setController(self::class)
                ->setAction(Action::NEW)
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
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $this->configureCommonActions($actions)
            ->disable(Action::SAVE_AND_CONTINUE);
    }
}
