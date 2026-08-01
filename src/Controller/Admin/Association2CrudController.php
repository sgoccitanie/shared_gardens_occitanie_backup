<?php

namespace App\Controller\Admin;

use App\Entity\Association;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RedirectResponse;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use App\Controller\Admin\Traits\EasyAdminAssetsTrait;
use App\Controller\Admin\Traits\EasyAdminActionsTrait;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class Association2CrudController extends AbstractCrudController
{
    use EasyAdminAssetsTrait;
    use EasyAdminActionsTrait;

    public function __construct(
        private readonly ManagerRegistry $doctrine,        
        private LoggerInterface $logger
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
            ->setFormThemes(['@EasyAdmin/crud/form_theme.html.twig', 'admin/posts/form.html.twig'])
        ;
    }
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name')->setLabel('Nom'),
            TextField::new('mantra')->setLabel('Mantra'),
            TextField::new('mobile')->setLabel('Téléphone'),
            TextareaField::new('description')->setLabel('Description'),

            ImageField::new('logo')
                ->setLabel('Logo')
                ->setUploadDir('public/uploads/profiles/SDJ/logo/')  // Chemin relatif à 'public/'
                ->setBasePath('/uploads/profiles/SDJ/logo/'),       // Pour l'affichage

            ImageField::new('banner')
                ->setLabel('Bannière')
                ->setUploadDir('public/uploads/profiles/SDJ/banner/')
                ->setBasePath('/uploads/profiles/SDJ/banner/'),
        ];
    }

    public function configureAssets(Assets $assets): Assets
    {
        return $this->configureCommonAssets($assets);
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = $this->configureCommonActions($actions);

        $deleteBannerAndLogo = Action::new('deleteBannerAndLogo', 'Supprimer', 'fa fa-trash')
            ->linkToCrudAction('deleteBannerAndLogo')
            ->setCssClass('btn btn-danger')
            ->setHtmlAttributes(['onclick' => 'return confirm("Confirmer la suppression ?");']);

        return $actions
            ->add(Crud::PAGE_INDEX, $deleteBannerAndLogo)
            ->remove(Crud::PAGE_INDEX, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW);
    }

     public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        /** @var Association $entityInstance */
        try {
            parent::updateEntity($entityManager, $entityInstance);
            $this->addFlash(
                'success',
                'Le profile association a été modifié avec succès.'
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur modification profile de l\'association : ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            $this->addFlash('danger', "Les modifications du profile n'ont pas pu être enregistrées. Réessayez ou contactez l'administrateur.");
        }
    }


    public function deleteBannerAndLogo(AdminContext $context): RedirectResponse
    {
        $association = $context->getEntity()->getInstance();
        $em = $this->doctrine->getManager();

        if ($association) {
            // Supprimer l'entité complètement pour que la ligne disparaisse
            $em->remove($association);
            $em->flush();
        }

        // Rediriger vers la page actuelle de la liste
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $url = $adminUrlGenerator
            ->setController(self::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }
}
