<?php

namespace App\Controller\Admin;

use App\Form\UserProfileType;
use App\Service\PictureService;
use App\Service\Utils;
use App\Service\HeaderService;
use App\Repository\AssociationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    public function __construct(
        private PictureService $pictureService,
        private HeaderService $headerService,
        private AssociationRepository $assoRepo
    ) {}

    #[Route('/admin/profile', name: 'app_admin_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        $pageTitle = 'Gestion du profil';

        // Récupérer l'ID de l'association active
        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $assoId = $firstAssociation ? $firstAssociation->getId() : 1;

        // Récupérer les données de l'association
        $headerData = $this->headerService->getHeaderData($assoId);
        $logoPath = $this->headerService->getLogoPath($headerData['assoLogo']);
        $formattedMantra = $this->headerService->getFormattedMantra($headerData['assoMantra']);

        // Déterminer le rôle principal de l'utilisateur
        $userRole = $this->getUserRole($user);

        return $this->render('admin/profile/index.html.twig', [
            'user' => $user,
            'pageTitle' => $pageTitle,
            'userRole' => $userRole,
            'headerData' => $headerData,
            'logoPath' => $logoPath,
            'formattedMantra' => $formattedMantra,
        ]);
    }

    /**
     * Détermine le rôle principal de l'utilisateur.
     */
    private function getUserRole($user): string
    {
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return 'administrateur.trice';
        } else {
            return 'éditeur.trice';
        }
    }

    #[Route('/admin/profile/update', name: 'app_admin_profile_update', methods: ['GET', 'POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserProfileType::class, $this->getUser());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setUpdatedAt(new \DateTimeImmutable());
            /*$featuredImage = $form->get('img')->getData();
            if ($featuredImage != null) {
                $image = $this->pictureService->square($featuredImage, 'profiles/users', 300);
                $user->setImg($image);
            }*/
            /*$user->setMobile(Utils::cleanInputStatic($form->get('mobile')->getData()));*/
            $user->setLogin(Utils::cleanInputStatic($form->get('login')->getData()));
            $user->setFirstname(Utils::cleanInputStatic($form->get('firstname')->getData()));
            $user->setLastname(Utils::cleanInputStatic($form->get('lastname')->getData()));
            try {
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Profil mis à jour avec succès');
                return $this->redirectToRoute('app_admin_profile');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour du profil =/. Veuillez réessayer.');
            }
            return $this->redirectToRoute('app_admin_profile');
        }
        $pageTitle = 'Gestion du profil';
        return $this->render('admin/profile/update.html.twig', [
            'controller_name' => 'ProfileController',
            'form' => $form->createView(),
            'pageTitle' => $pageTitle
        ]);
    }
}
