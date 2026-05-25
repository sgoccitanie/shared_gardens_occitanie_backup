<?php

namespace App\Controller\Admin;

use App\Entity\Addresses;
use App\Entity\Association;
use App\Entity\Categories;
use App\Entity\Keywords;
use App\Entity\Links;
use App\Entity\Posts;
use App\Entity\Presentation;
use App\Entity\SubjectEmail;
use App\Entity\Tabs;
use App\Entity\User;
use App\Service\HeaderService;
use App\Repository\AssociationRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EDITOR')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private HeaderService $headerService,
        private AssociationRepository $assoRepo,
        private Security $security
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Bloquer pour les ghostés
        $user = $this->getUser();
        if ($user instanceof \App\Entity\User && $user->isGhosted()) {
            $this->addFlash('error', 'Cet espace est réservé aux administrateurs.');
            return $this->redirectToRoute('app_login');
        }

        // Récupérer l'ID de l'association active
        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $assoId = $firstAssociation ? $firstAssociation->getId() : 1;

        // Récupérer les données de l'association
        $headerData = $this->headerService->getHeaderData($assoId);
        $logoPath = $this->headerService->getLogoPath($headerData['assoLogo']);
        $formattedMantra = $this->headerService->getFormattedMantra($headerData['assoMantra']);

        return $this->render('admin/menu_dashboard.html.twig', [
            'headerData' => $headerData,
            'logoPath' => $logoPath,
            'formattedMantra' => $formattedMantra,
        ]);
    }

    #[Route('/admin/crud', name: 'admin_crud')]
    public function crud(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        return $this->redirect($adminUrlGenerator->setController(PostsCrudController::class)->generateUrl());
    }

    // Gestion des notifications
    #[Route('/admin/comments', name: 'admin_comments')]
    public function comments(): Response
    {
        return $this->redirectToRoute('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => 'App\\Controller\\Admin\\CommentCrudController'
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle(' Réseau des Semeurs de Jardins')
            ->setFaviconPath('uploads/profiles/SDJ/logo/logo_SDJ.png')
            ->disableDarkMode();
    }

    public function configureMenuItems(): iterable
    {

        /*************************************************
         * Les editeurs peuvent uniquement :
         *    - modifier leur propre profil, 
         *    - CRUD leurs propres articles 
         *    - Gérer les commentaires
         **************************************************/

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Sous-élément 'Articles' du Blog :  visible par les roles 'editor' et 'admin'
        $blogSubMenu = [
            MenuItem::linkToCrud('Articles', 'fas fa-newspaper', Posts::class),
        ];

        // Tous les autres sous-éléments du Blog : uniquement pour les admins
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $blogSubMenu = array_merge($blogSubMenu, [
                MenuItem::linkToCrud('Images/Logos', 'fas fa-image', Association::class)->setController(Association2CrudController::class),
                MenuItem::linkToCrud('Onglets', 'fas fa-tags', Tabs::class),
                MenuItem::linkToCrud('Mots clés', 'fas fa-key', Keywords::class),
                MenuItem::linkToCrud('Catégories', 'fas fa-list', Categories::class),
                MenuItem::linkToCrud('Liens', 'fas fa-link', Links::class),
            ]);
        }

        yield MenuItem::subMenu('Blog', 'fas fa-newspaper')->setSubItems($blogSubMenu);

        // Menu Présentation : UNIQUEMENT pour les ADMINS
        if ($this->security->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkToCrud('Présentation', 'fas fa-home', Presentation::class)
                ->setController(PresentationCrudController::class);
        }

        // Menu RSJ : uniquement pour les admins
        if ($this->security->isGranted('ROLE_ADMIN')) {
            yield MenuItem::subMenu('RSJ', 'fas fa-file-alt')->setSubItems([
                MenuItem::linkToCrud('Page contact', 'fas fa-envelope', SubjectEmail::class),
                MenuItem::linkToCrud('Informations', 'fas fa-building', Association::class),
                MenuItem::linkToCrud('Coordonnées', 'fas fa-map-marker-alt', Addresses::class),
            ]);
        }

        // Menu Utilisateurs : uniquement pour les admins
        if ($this->security->isGranted('ROLE_ADMIN')) {
            yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        }
    }
}
