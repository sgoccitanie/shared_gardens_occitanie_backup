<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\JWTService;
use App\Service\MessagerieService;
use App\Service\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly JWTService $JWTService,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'monolog.logger.security')]
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/register', name: 'admin_registration')]
    #[IsGranted('ROLE_ADMIN')]
    public function register(
        MessagerieService $messagerie,
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $repository
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $emailReceiver = $form->get('email')->getData();

            if (empty($repository->findOneBy(['email' => $emailReceiver]))) {
                // Récupération des rôles avec filtrage strict
                $roles = $form->get('roles')->getData();
                if (is_string($roles)) {
                    $roles = [$roles];
                }

                $cleanedRoles = [];
                foreach ($roles as $role) {
                    $cleanedRole = Utils::cleanInputStatic($role);
                    if (in_array($cleanedRole, ['ROLE_ADMIN', 'ROLE_EDITOR'])) {
                        $cleanedRoles[] = $cleanedRole;
                    }
                }

                // PAS de cleanInputStatic sur le mot de passe
                $plainPassword = $form->get('password')->getData();
                $login = Utils::cleanInputStatic($form->get('login')->getData());
                $assoUser = $form->get('user_asso')->getData();

                // Setters
                $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
                $user->setTokenExpirateAt(new \DateTimeImmutable('+1 day'));
                $user->setCreatedAt(new \DateTimeImmutable());
                $user->setLogin($login);
                $user->setToken('TokenSent');
                $user->setRoles($cleanedRoles);
                $user->setUserAsso($assoUser instanceof \App\Entity\Association ? $assoUser : null);

                try {
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();

                    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
                    $payload = ['user_id' => $user->getId()];
                    // Token valide 24h (cohérent avec le message)
                    $token = JWTService::generate($header, $payload, $this->getParameter('app.jwtsecret'), 86400);

                    $emailSent = $messagerie->sendMail(
                        'Validation de votre compte',
                        $user->getEmail(),
                        'email/confirmation_email.html.twig',
                        [
                            'token' => $token,
                            'login' => $user->getLogin(),
                            'expiresAtMessageData' => '24 heures',
                        ]
                    );

                    if ($emailSent) {
                        $this->logger->info('Nouveau compte créé: {email}', ['email' => $emailReceiver]);
                        $this->addFlash('success', 'Compte créé. Un email de confirmation a été envoyé.');
                    } else {
                        $this->logger->error('Échec envoi email de confirmation pour: {email}', ['email' => $emailReceiver]);
                        $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email. Veuillez réessayer.');
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Erreur création utilisateur: ' . $e->getMessage(), [
                        'email' => $emailReceiver,
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $this->addFlash('error', 'Erreur lors de l\'enregistrement. Veuillez réessayer.');
                }
            } else {
                // Message générique anti-énumération
                $this->logger->warning('Tentative de création avec email déjà existant: {email}', ['email' => $emailReceiver]);
                $this->addFlash('error', 'Erreur dans la saisie de l\'email.');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'email_sent' => false,
            'pageTitle' => 'Inscription',
        ]);
    }

    #[Route('/verify/{token}', name: 'app_verify_email')]
    public function verifyUserEmail(
        string $token,
        UserRepository $repository,
        EntityManagerInterface $entityManager
    ): Response {
        if (
            !$this->JWTService->isValid($token)
            || $this->JWTService->isExpired($token)
            || !$this->JWTService->check($token, $this->getParameter('app.jwtsecret'))
        ) {
            $this->logger->warning('Tentative de validation avec token invalide ou expiré');
            $this->addFlash('error', 'Erreur, votre lien n\'est pas correct ou a expiré.');
            return $this->redirectToRoute('app_login');
        }

        $payload = $this->JWTService->getPayload($token);
        $user = $repository->find($payload['user_id']);

        // Check si l'utilisateur existe
        if (!$user) {
            $this->logger->warning('Tentative de validation pour utilisateur introuvable: {user_id}', [
                'user_id' => $payload['user_id'] ?? 'inconnu',
            ]);
            $this->addFlash('error', 'Erreur, utilisateur introuvable.');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setToken('noToken');
        $user->setTokenExpirateAt(new \DateTimeImmutable('00:00:00'));

        try {
            $entityManager->flush();
            $this->logger->info('Compte validé: {email}', ['email' => $user->getEmail()]);
            $this->addFlash('success', 'Votre compte a été validé avec succès !');
        } catch (\Exception $exception) {
            $this->logger->error('Erreur lors de la validation: ' . $exception->getMessage());
            $this->addFlash('error', 'Erreur, la validation a échoué.');
        }

        return $this->redirectToRoute('app_login');
    }
}
