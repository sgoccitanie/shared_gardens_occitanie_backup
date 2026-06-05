<?php

namespace App\Controller\Admin;

use App\Form\ResetPasswordFormType;
use App\Form\ResetPwdFormType;
use App\Repository\UserRepository;
use App\Service\JWTService;
use App\Service\MessagerieService;
use App\Service\HeaderService;
use App\Repository\AssociationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class SecurityController extends AbstractController
{
    private VerifyEmailHelperInterface $verifyEmailHelper;
    private HeaderService $headerService;
    private AssociationRepository $assoRepo;
    private $logger;

    public function __construct(
        VerifyEmailHelperInterface $verifyEmailHelper,
        HeaderService $headerService,
        AssociationRepository $assoRepo,
        LoggerInterface $logger
    ) {
        $this->verifyEmailHelper = $verifyEmailHelper;
        $this->headerService = $headerService;
        $this->assoRepo = $assoRepo;
        $this->logger = $logger;
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils, UserRepository $repository, EntityManagerInterface $entityManager): Response
    {
        // Si l'utilisateur est connecté
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_EDITOR') || $this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('admin');
            }
            return $this->redirectToRoute('app_home');
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // Vérifier si un email a été soumis
        if ($lastUsername) {
            $user = $repository->findOneBy(['email' => $lastUsername]);
            if ($user && $user->isGhosted()) {
                $this->addFlash('error', 'Cet espace est réservé aux administrateurs du site.');
                $this->logger->warning('Tentative de connexion par un utilisateur ghosté: {email}', ['email' => $lastUsername]);
                return $this->redirectToRoute('app_login');
            }
        }
        if ($error) {
            $this->logger->warning('Tentative de connexion échouée pour: {email}', ['email' => $lastUsername ?? 'inconnu']);
        }

        // Récupérer les données de l'association
        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $assoId = $firstAssociation ? $firstAssociation->getId() : 1;
        $headerData = $this->headerService->getHeaderData($assoId);
        $logoPath = $this->headerService->getLogoPath($headerData['assoLogo']);
        $formattedMantra = $this->headerService->getFormattedMantra($headerData['assoMantra']);

        return $this->render('security/index.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername,
            'csrf_token_intention' => 'authenticate',
            'target_path' => null,
            'username_parameter' => '_email',
            'password_parameter' => '_password',
            'remember_me_enabled' => true,
            'remember_me_checked' => true,
            'forgot_password_enabled' => true,
            'forgot_password_path' => $this->generateUrl('app_admin_forgot_password'),
            'headerData' => $headerData,
            'logoPath' => $logoPath,
            'formattedMantra' => $formattedMantra,
        ]);
    }

    #[Route(path: '/forget-password', name: 'app_admin_forgot_password')]
    public function forgetPassword(Request $request, MessagerieService $messagerie, UserRepository $repository): Response
    {
        // Check if the user is already logged in
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('admin_dashboard');
        }
        // Disable unique check for this form
        $loginForm = $this->createForm(ResetPwdFormType::class);
        $loginForm->handleRequest($request);
        $emailStatut = false;
        // Test if form is submitted and valid
        if ($loginForm->isSubmitted() && $loginForm->isValid()) {
            /** @var string $emailReceiver */
            $emailReceiver = $loginForm->get('email')->getData();
            // Send email
            $user = $repository->findOneBy(['email' => $emailReceiver]);
            if (!empty($user)) {
                $header = [
                    'alg' => 'HS256',
                    'typ' => 'JWT',
                ];
                $payload = [
                    'user_id' => $user->getId(),
                ];
                // Generate token
                $token = JWTService::generate($header, $payload, $this->getParameter('app.jwtsecret'), 1800);
                $user->setToken($token);
                // Generate url to the reset password page
                $url = $this->generateUrl('app_admin_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
                $messagerie->sendMail('Réinitialisation du mot de passe', $user->getEmail(), 'email/reset_pwd.html.twig', [
                    'token' => $token,
                    'login' => $user->getLogin(),
                    'expiresAtMessageData' => '30 minutes',
                    'url' => $url
                ]);
                $emailStatut = true;
                $this->addFlash('success', 'Un email de réinitialisation a été envoyé à l\'adresse ' . $emailReceiver . '.');
            } else {
                $this->addFlash('error', 'Le compte n\'existe pas.');
            }
        }
        return $this->render('security/forgot_password.html.twig', [
            'loginForm' => $loginForm,
            'email_sent' => $emailStatut,
        ]);
    }

    #[Route(path: '/reset-password/{token}', name: 'app_admin_reset_password')]
    public function resetPassword(UserPasswordHasherInterface $passwordHasher, string $token, Request $request, JWTService $JWTService, UserRepository $repository, EntityManagerInterface $entityManager): Response
    {
        if ($JWTService->isValid($token) &&  !$JWTService->isExpired($token) && $JWTService->check($token, $this->getParameter('app.jwtsecret'))) {
            $payload = $JWTService->getPayload($token);
            $user = $repository->find($payload['user_id']);
            $form = $this->createForm(ResetPasswordFormType::class);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                if ($user && $user->getIsVerified()) {
                    $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));
                    $user->setToken('noToken');
                    $user->setUpdatedAt(new \DateTimeImmutable());
                    $user->setTokenExpirateAt(new \DateTimeImmutable('00:00:00'));
                    $entityManager->flush();
                    try {
                        $entityManager->flush();
                        $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès !');
                    } catch (\Exception $exception) {
                        $this->addFlash('error', 'Erreur, le mot de passe n\'a pas pu être mis à jour =/');
                    }
                    return $this->redirectToRoute('app_login');
                } else {
                    $this->addFlash('error', 'Erreur, votre lien a expiré ou bien votre compte n\'est pas vérifié =/');
                }
            }
        } else {
            $this->addFlash('error', 'Erreur, Le lien n\'est pas valide ou a expiré =/');
        }
        return $this->render('security/reset_password.html.twig', [
            'form' => $form->createView(),
            'token' => $token,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
