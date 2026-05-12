<?php

namespace App\Security;

use Psr\Log\LoggerInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class FormLoginAuthenticator extends AbstractAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordEncoder;
    private LoggerInterface $logger;
    private UserRepository $userRepository;
    private RouterInterface $router;
    private UrlGeneratorInterface $urlGenerator;
    private Security $security;
    private FlashBagInterface $flashBag;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        RouterInterface $router,
        UserRepository $userRepository,
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordEncoder,
        Security $security,
        FlashBagInterface $flashBag
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->router = $router;
        $this->userRepository = $userRepository;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->security = $security;
        $this->flashBag = $flashBag;
    }

    protected function getLoginUrl(): string
    {
        return $this->router->generate(self::LOGIN_ROUTE);
    }

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $credentials = $this->getCredentials($request);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);

        if (!$user || !$user->getIsVerified()) {
            throw new CustomUserMessageAuthenticationException('Votre compte n\'est pas encore actif. Un email doit vous attendre dans votre messagerie privée.');
        }

        if (!$this->passwordEncoder->isPasswordValid($user, $credentials['password'])) {
            throw new CustomUserMessageAuthenticationException('Les données renseignées ne sont pas correctes.');
        }

        return new Passport(
            new UserBadge($user->getEmail()),
            new PasswordCredentials($credentials['password']),
            [
                new CsrfTokenBadge('authenticate', $credentials['token'])
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        // Si l'utilisateur a le rôle ROLE_EDITOR ou ROLE_ADMIN, rediriger vers espace admin
        if ($this->security->isGranted('ROLE_EDITOR') || $this->security->isGranted('ROLE_ADMIN')) {
            return new RedirectResponse($this->router->generate('admin'));
        }

        // Si l'utilisateur a le role ROLE_USER, rediriger vers la page d'accueil
        $this->flashBag->add('info', 'Vous êtes connecté en tant qu\'utilisateur.');
        return new RedirectResponse($this->router->generate('app_home'));
    }

    public function getCredentials(Request $request): array
    {
        return [
            'email' => $request->request->get('_email'),
            'password' => $request->request->get('_password'),
            'token' => $request->request->get('_csrf_token'),
        ];
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->flashBag->add('error', 'Vous n\'êtes pas autorisé à gérer cet espace.');
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
