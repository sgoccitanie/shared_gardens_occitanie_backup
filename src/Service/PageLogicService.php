<?php

namespace App\Service;

use App\Repository\TabsRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PageLogicService
{
    public function __construct(
        private RequestStack $requestStack,
        private TabsRepository $tabsRepository,
    ) {}

    /**
     * Génèrer le contexte de la page
     */
    public function getPageContext(array $params): array
    {
        $request = $this->requestStack->getCurrentRequest();

        // Récupérer des paramètres
        $slug = $params['slug'] ?? null;
        $id = $params['id'] ?? null;
        $posts = $params['posts'] ?? [];
        $pages = $params['pages'] ?? [];
        $headerData = $params['headerData'] ?? [];
        $orderParam = $params['order'] ?? null;
        $backToListParam = $params['backToList'] ?? false;
        $currentRoute = $params['currentRoute'] ?? '';
        $isHomeRoute = $params['isHomeRoute'] ?? false;

        // Définir l'ordre de tri
        $order = in_array($orderParam, ['ASC', 'DESC']) ? $orderParam : 'DESC';

        // Conditions métier
        $displayList = !isset($slug) || $slug !== 'coming';
        $eventCalendar = ($slug === 'coming');

        // Gestion du retour à la liste
        $backToList = (bool)$backToListParam;

        // Flags pour la vue
        $isHome = ($slug === null && $id === null);

        // Récupérer le slug via l'id si nécessaire
        if ($slug === null && $id !== null) {
            $tab = $this->tabsRepository->find($id);
            if ($tab !== null) {
                $slug = $tab->getSlug();
            } else {
                $slug = null; // Peut être géré différemment si besoin
            }
        }

        // Ajouter des flags pour le template
        $routeName = $currentRoute;

        // Flag pour savoir si on est sur la page d'accueil
        $isHomePage = ($slug === null && $id === null);

        // Flag pour la route
        $isHomeRoute = ($routeName === 'app_home');

        return [
            'slug' => $slug,
            'order' => $order,
            'posts' => $posts,
            'pages' => $pages,
            'headerData' => $headerData,
            'displayList' => $displayList,
            'eventCalendar' => $eventCalendar,
            'backToList' => $backToList,
            'isHome' => $isHome,
            'currentRoute' => $currentRoute,
            // Flags pour Twig
            'isHomePage' => $isHomePage,
            'isHomeRoute' => $isHomeRoute,
        ];
    }
}
