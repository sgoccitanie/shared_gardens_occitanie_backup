<?php

namespace App\Controller\Admin;

use App\Form\UserProfileType;
use App\Service\PictureService;
use App\Service\Utils;
use App\Service\CommonDataService;
use App\Repository\AssociationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request as Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    public function __construct(
        private CommonDataService $commonDataService,
        private AssociationRepository $assoRepo
    ) {}

    #[Route('/admin/profile', name: 'app_admin_profile')]
    public function index(): Response
    {
        $user = $this->getUser();
        $pageTitle = 'Gestion du profil';

        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $headerData = $this->commonDataService->getFullHeaderData();
        $formattedMantra = $this->commonDataService->getFormattedMantra($headerData['assoMantra']);

        // Rôle de l'utilisateur
        $userRole = $this->getUserRole($user);

        return $this->render('admin/profile/index.html.twig', [
            'user' => $user,
            'pageTitle' => $pageTitle,
            'userRole' => $userRole,
            'headerData' => $headerData,
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
    public function update(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserProfileType::class, $this->getUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $user->setUpdatedAt(new \DateTimeImmutable());

            // Mise à jour des champs de base du profil
            $user->setLogin(Utils::cleanInputStatic($form->get('login')->getData()));
            $user->setFirstname(Utils::cleanInputStatic($form->get('firstname')->getData()));
            $user->setLastname(Utils::cleanInputStatic($form->get('lastname')->getData()));

            // Modifier le pwd
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

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
