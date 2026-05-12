<?php

namespace App\Twig\Extension;

use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class MenuExtension extends AbstractExtension
{
    public function __construct(
        private SecurityBundleSecurity $security,
        private UrlGeneratorInterface $urlGenerator,
        private RequestStack $requestStack
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('main_menu', [$this, 'getMainMenu']),
            new TwigFunction('is_menu_active', [$this, 'isMenuActive']),
        ];
    }

    public function getMainMenu(): array
    {
        $menu = [
            'home' => [
                'label' => 'Accueil',
                'route' => 'app_home',
                'roles' => ['PUBLIC_ACCESS']
            ],
            'jardins' => [
                'label' => 'Carte des jardins',
                'route' => 'app_jardins',
                'roles' => ['PUBLIC_ACCESS']
            ],
        ];

        // Menu pour les associations (ROLE_EDITOR)
        if ($this->security->isGranted('ROLE_EDITOR')) {
            $menu['association'] = [
                'label' => 'Mon Association',
                'route' => 'app_association',
                'roles' => ['ROLE_ASSOCIATION'],
                'children' => [
                    'membres' => [
                        'label' => 'Membres',
                        'route' => 'app_association_membres'
                    ],
                ]
            ];
        }

        // Menu Admin (ROLE_ADMIN)
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $menu['admin'] = [
                'label' => 'Administration',
                'route' => 'admin_dashboard',
                'roles' => ['ROLE_ADMIN']
            ];
        }

        return $menu;
    }

    public function isMenuActive(string $route): bool
    {
        $currentRoute = $this->requestStack->getCurrentRequest()->get('_route');
        return str_starts_with($currentRoute, $route);
    }
}
