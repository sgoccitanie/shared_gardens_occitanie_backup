<?php

namespace App\Controller\Admin;

use App\Form\ResetPasswordFormType;
use App\Form\ResetPwdFormType;
use App\Repository\UserRepository;
use App\Service\JWTService;
use App\Service\MessagerieService;
use App\Service\CommonDataService;
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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class SecurityController extends AbstractController
{
    public function __construct(
        private readonly CommonDataService $commonDataService,
        private readonly AssociationRepository $assoRepo,
        #[Autowire(service: 'monolog.logger.security')]
        private readonly LoggerInterface $logger,
        private RateLimiterFactory $resetPasswordLimiter,
    ) {}

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
        $headerData = $this->commonDataService->getHeaderData($assoId);

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
            'headerData' => $headerData
        ]);
    }

    #[Route(path: '/forget-password', name: 'app_admin_forgot_password')]
    public function forgetPassword(
        Request $request,
        MessagerieService $messagerie,
        UserRepository $repository,
        EntityManagerInterface $entityManager,
        RateLimiterFactory $forgotPasswordLimiter
    ): Response {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('admin');
        }

        $loginForm = $this->createForm(ResetPwdFormType::class);
        $loginForm->handleRequest($request);
        $emailStatut = false;

        if ($loginForm->isSubmitted() && $loginForm->isValid()) {
            // Rate limiting par IP
            $limiter = $forgotPasswordLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                $this->logger->warning('Rate limit atteint pour forgot password: {ip}', ['ip' => $request->getClientIp()]);
                $this->addFlash('error', 'Trop de tentatives. Veuillez réessayer dans 1 heure.');
                return $this->redirectToRoute('app_login');
            }

            $emailReceiver = $loginForm->get('email')->getData();
            $this->logger->info('Demande de reset password pour: {email}', ['email' => $emailReceiver]);

            $user = $repository->findOneBy(['email' => $emailReceiver]);

            if (!empty($user)) {
                $header = ['alg' => 'HS256', 'typ' => 'JWT'];
                $payload = ['user_id' => $user->getId()];
                $token = JWTService::generate($header, $payload, $this->getParameter('app.jwtsecret'), 1800);

                $user->setToken($token);
                $url = $this->generateUrl(
                    'app_admin_reset_password',
                    ['token' => $token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );

                try {
                    $emailSent = $messagerie->sendMail(
                        'Réinitialisation du mot de passe',
                        $user->getEmail(),
                        'email/reset_pwd.html.twig',
                        [
                            'token' => $token,
                            'login' => $user->getLogin(),
                            'expiresAtMessageData' => '30 minutes',
                            'url' => $url,
                        ]
                    );

                    if ($emailSent) {
                        $entityManager->flush();
                        $emailStatut = true;
                        $this->logger->info('Email de reset envoyé pour: {email}', ['email' => $emailReceiver]);
                    } else {
                        $this->logger->error('Échec envoi email de reset pour: {email}', ['email' => $emailReceiver]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Erreur envoi mail reset: ' . $e->getMessage(), [
                        'email' => $emailReceiver,
                    ]);
                }
            } else {
                $this->logger->info('Demande de reset pour email inexistant: {email}', ['email' => $emailReceiver]);
            }

            // Message identique dans tous les cas (anti-énumération des comptes enregistrés)
            $this->addFlash('success', 'Si un compte existe avec cet email, un lien de réinitialisation vous a été envoyé.');
        }

        return $this->render('security/forgot_password.html.twig', [
            'loginForm' => $loginForm,
            'email_sent' => $emailStatut,
        ]);
    }

    #[Route(path: '/reset-password/{token}', name: 'app_admin_reset_password')]
    public function resetPassword(
        UserPasswordHasherInterface $passwordHasher,
        string $token,
        Request $request,
        JWTService $JWTService,
        UserRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        $form = $this->createForm(ResetPasswordFormType::class);

        // Vérification de la validité du token JWT
        if (
            !$JWTService->isValid($token)
            || $JWTService->isExpired($token)
            || !$JWTService->check($token, $this->getParameter('app.jwtsecret'))
        ) {
            $this->logger->warning('Tentative de reset avec token JWT invalide ou expiré');
            $this->addFlash('error', 'Le lien n\'est pas valide ou a expiré.');
            return $this->redirectToRoute('app_login');
        }

        $payload = $JWTService->getPayload($token);
        $user = $repository->find($payload['user_id']);

        // Vérification que l'utilisateur existe
        if (!$user) {
            $this->logger->warning('Tentative de reset pour utilisateur introuvable: {user_id}', [
                'user_id' => $payload['user_id'] ?? 'inconnu',
            ]);
            $this->addFlash('error', 'Le lien n\'est pas valide.');
            return $this->redirectToRoute('app_login');
        }

        // Vérification que le token n'a pas déjà été utilisé
        if ($user->getToken() === 'noToken') {
            $this->logger->warning('Tentative de réutilisation de token de reset: {email}', [
                'email' => $user->getEmail(),
            ]);
            $this->addFlash('error', 'Ce lien a déjà été utilisé.');
            return $this->redirectToRoute('app_login');
        }

        // Vérification que le token correspond bien à celui en base
        if ($user->getToken() !== $token) {
            $this->logger->warning('Token reçu ne correspond pas au token en base pour: {email}', [
                'email' => $user->getEmail(),
            ]);
            $this->addFlash('error', 'Ce lien n\'est plus valide.');
            return $this->redirectToRoute('app_login');
        }

        // Vérification que le compte est validé
        if (!$user->getIsVerified()) {
            $this->logger->warning('Tentative de reset pour compte non vérifié: {email}', [
                'email' => $user->getEmail(),
            ]);
            $this->addFlash('error', 'Votre compte n\'est pas encore vérifié.');
            return $this->redirectToRoute('app_login');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $this->resetPasswordLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                throw new TooManyRequestsHttpException();
            }
            $user->setPassword($passwordHasher->hashPassword($user, $form->get('password')->getData()));
            $user->setToken('noToken');
            $user->setUpdatedAt(new \DateTimeImmutable());
            $user->setTokenExpirateAt(new \DateTimeImmutable('00:00:00'));

            try {
                $entityManager->flush();
                $this->logger->info('Mot de passe réinitialisé pour: {email}', ['email' => $user->getEmail()]);
                $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès !');
                return $this->redirectToRoute('app_login');
            } catch (\Exception $exception) {
                $this->logger->error('Erreur lors du reset password: ' . $exception->getMessage(), [
                    'email' => $user->getEmail(),
                ]);
                $this->addFlash('error', 'Erreur, le mot de passe n\'a pas pu être mis à jour.');
            }
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
