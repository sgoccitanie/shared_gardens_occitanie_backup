<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Comment;
use App\Form\RegistrationFormType;
use App\Form\CommentType;
use App\Repository\CategoriesRepository;
use App\Repository\AddressesRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PagesRepository;
use App\Repository\PostsRepository;
use App\Repository\TabsRepository;
use App\Repository\PresentationRepository;
use App\Service\PageLogicService;
use App\Repository\AssociationRepository;
use App\Service\CommonDataService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class HomeController extends AbstractController
{
    private $pageTitle = 'Accueil | Réseau des Semeurs de Jardins';

    public function __construct(
        private readonly PostsRepository $postsRepository,
        private readonly TabsRepository $tabsRepository,
        private readonly PagesRepository $pagesRepository,
        private readonly PageLogicService $pageLogicService,
        private readonly RequestStack $requestStack,
        private readonly AssociationRepository $assoRepo,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly CategoriesRepository $categoriesRepository,
        private readonly PresentationRepository $presentationRepository,
        private readonly CommonDataService $commonDataService,
        private readonly LoggerInterface $logger,
    ) {}


    // Tous les articles
    #[Route('/articles', name: 'app_post_show', methods: ['GET'])]
    public function articles(Request $request): Response
    {
        // Validation des paramètres
        $order = $this->validateOrder($request->query->get('order', 'DESC'));
        $categoryId = $this->validateCategoryId($request->query->get('category'));

        // Filtrer catégorie
        $categoryId = $request->query->get('category');

        try {
            // Filtrer par catégorie SI présent
            if ($categoryId) {
                $posts = $this->postsRepository->createQueryBuilder('p')
                    ->innerJoin('p.categories', 'c')
                    ->andWhere('c.id = :catId')
                    ->setParameter('catId', $categoryId)
                    ->andWhere('p.status = 1')
                    ->orderBy('p.posted_at', $order)
                    ->getQuery()
                    ->getResult();
            } else {
                // Comportement normal (TOUS les articles)
                $posts = $this->postsRepository->findBy(
                    ['status' => 1],
                    ['posted_at' => $order]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération articles: ' . $e->getMessage());
            $posts = [];
        }

        // Rediriger vers l'article
        $postsWithUrls = $this->commonDataService->buildPostsWithUrls($posts);
        // Redirection des sous-onglets avec allPostsWithUrls 
        try {
            $allPosts = $this->postsRepository->findBy(['status' => 1], ['posted_at' => 'DESC']);
        } catch (\Exception $e) {
            $allPosts = [];
        }
        $allPostsWithUrls = [];
        foreach ($allPosts as $post) {
            $parameters = ['id' => $post->getId()];
            $postUrl = $this->urlGenerator->generate('app_home_with_id', $parameters);
            $allPostsWithUrls[] = [
                'post' => $post,
                'url' => $postUrl,
            ];
        }

        // Récupérer les catégories avec leurs tabs associés
        $categoriesWithTabs = [];
        $categories = $this->categoriesRepository->findAll();
        foreach ($categories as $category) {
            $tabs = $this->tabsRepository->findBy(['category' => $category]);
            $categoriesWithTabs[] = [
                'category' => $category,
                'tabs' => $tabs,
            ];
        }
        // Récupérer le logo et la bannière
        $headerData = $this->commonDataService->getFullHeaderData();

        try {
            $pages = $this->pagesRepository->findAll();
        } catch (\Exception $e) {
            $pages = [];
        }

        return $this->render('home/index.html.twig', [
            'headerData' => $headerData,
            'pageTitle' => 'Tous les articles',

            'pages' => $pages,
            'postsWithUrls' => $postsWithUrls,

            'displayList' => true,
            'eventCalendar' => false,
            'order' => $order,
            'backToList' => false,
            'isHomeRoute' => false,

            'commentForm' => null,
            'comments' => [],
            'commentCount' => 0,
            'id' => null,

            'categories' => $this->categoriesRepository->findAll(),
            'allPostsWithUrls' => $allPostsWithUrls,
            'categoriesWithTabs' => $categoriesWithTabs,
        ]);
    }

    #[Route('/{id}', name: 'app_home_with_id', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[Route('/{slug?}/{id?}', name: 'app_home_with_slug_and_id', methods: ['GET'])]
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(?string $slug = null, ?int $id = null): Response
    {
        // Liste des slugs statiques à exclure
        $staticSlugs = ['rgpd', 'mentions-legales', 'politique-de-confidentialite', 'cookies', 'qui-sommes-nous'];

        // Rediriger vers la route statique si le slug correspond
        if (in_array($slug, $staticSlugs)) {
            return $this->redirectToRoute('app_' . str_replace('-', '_', $slug));
        }

        $request = $this->requestStack->getCurrentRequest();
        $adhesionUrl = 'https://www.helloasso.com/associations/le-reseau-des-semeurs-de-jardins/adhesions/adhesion-annuelle-au-reseau-des-semeurs-de-jardins-2026';

        // Récupérer TOUS les articles pour la logique des sous-onglets (dans toutees lels pages)
        try {
            $allPosts = $this->postsRepository->findBy(['status' => 1], ['posted_at' => 'DESC']);
        } catch (\Exception $e) {
            $allPosts = [];
        }
        $allPostsWithUrls = [];
        foreach ($allPosts as $post) {
            $parameters = ['id' => $post->getId()];
            $postUrl = $this->urlGenerator->generate('app_home_with_id', $parameters);
            $allPostsWithUrls[] = [
                'post' => $post,
                'url' => $postUrl,
            ];
        }

        // Récupérer les catégories avec leurs tabs associés
        $categoriesWithTabs = [];
        $categories = $this->categoriesRepository->findAll();
        foreach ($categories as $category) {
            $tabs = $this->tabsRepository->findBy(['category' => $category]);
            $categoriesWithTabs[] = [
                'category' => $category,
                'tabs' => $tabs,
            ];
        }

        // Page présentation
        if ($slug === null && $id === null) {
            $headerData = $this->commonDataService->getFullHeaderData();

            // Conversion sécurisée des données
            $headerData['assoDescription'] = is_array($headerData['assoDescription'] ?? null)
                ? implode(' ', array_filter($headerData['assoDescription'], 'is_scalar'))
                : ($headerData['assoDescription'] ?? 'Description non disponible');

            // Récupérer la présentation depuis la base de données
            $presentation = $this->presentationRepository->findOneBy([], ['id' => 'ASC']);
            $presentationContent = $presentation ? $presentation->getContent() : '
                <h2 class="mb-4 color-green">Bienvenue sur notre site</h2>
                <p class="lead text-center mb-4">
                    Notre association met en réseau les différents jardins collectifs présents ou en devenir du Languedoc Roussillon.
                </p>
                <p class="mb-4">
                    En 2020, nous avons lancé un projet ambitieux : planter des bosquets fruitiers sur l\'espace public de Montpellier !
                </p>
            ';


            return $this->render('home/presentation.html.twig', [
                'headerData' => $headerData,
                'presentationContent' => $presentationContent,
                'allPostsWithUrls' => $allPostsWithUrls,
                'categoriesWithTabs' => $categoriesWithTabs,
            ]);
        }

        // Récupérer des posts
        if ($id) {
            $post = $this->postsRepository->find($id);
            if (!$post || !$post->isStatus()) {
                $posts = [];
            } else {
                $posts = [$post];
            }
        } else {
            if ($slug) {
                try {
                    $postsQueryBuilder = $this->postsRepository->createQueryBuilder('p')
                        ->addSelect('t')
                        ->innerJoin('p.tab', 't')
                        ->andWhere('t.slug = :slug')
                        ->setParameter('slug', $slug)
                        ->andWhere('p.status = 1')
                        ->orderBy('p.posted_at', 'DESC');
                    $posts = $postsQueryBuilder->getQuery()->execute();
                } catch (\Exception $e) {
                    $posts = [];
                }
            } else {
                try {
                    $posts = $this->postsRepository->findBy(['status' => 1], ['posted_at' => 'DESC']);
                } catch (\Exception $e) {
                    $posts = [];
                }
            }
        }

        // Générer URLs pour chaque post
        $postsWithUrls = [];
        foreach ($posts as $post) {
            $parameters = ['id' => $post->getId()];
            $postUrl = $this->urlGenerator->generate('app_home_with_id', $parameters);
            $postsWithUrls[] = [
                'post' => $post,
                'url' => $postUrl,
            ];
        }

        // Données association et header
        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $headerData = $this->commonDataService->getFullHeaderData();

        $currentRoute = $request->attributes->get('_route');
        $isHomeRoute = in_array($currentRoute, ['app_home', 'app_home_with_id', 'app_home_with_slug_and_id']);
        $order = $request->query->get('order', 'DESC');

        // Pages
        try {
            $pages = $this->pagesRepository->findAll();
        } catch (\Exception $e) {
            $pages = [];
        }

        $displayList = !$id;
        $eventCalendar = ($slug === 'coming');

        $context = $this->pageLogicService->getPageContext([
            'slug' => $slug,
            'posts' => $posts,
            'pages' => $pages,
            'order' => $order,
            'backToList' => $id ? true : false,
            'currentRoute' => $currentRoute,
            'isHomeRoute' => $isHomeRoute,
        ]);

        // Variables commentaires et formulaire pour afficher un article unique
        $commentForm = null;
        $comments = [];
        $commentCount = 0;

        if ($id && isset($post) && $post && $post->isStatus()) {
            $comment = new Comment();
            $comment->setPost($post);

            $isEditorOrAdmin = $this->isGranted('ROLE_EDITOR') || $this->isGranted('ROLE_ADMIN');

            $form = $this->createForm(CommentType::class, $comment, [
                'is_editor_or_admin' => $isEditorOrAdmin,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user = $this->getUser();
                if ($user) {
                    $comment->setUser($user);

                    if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_EDITOR')) {
                        $comment->setPseudo((string) $user);
                    }
                }

                $this->entityManager->persist($comment);
                $this->entityManager->persist($post);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_home_with_id', ['id' => $id]);
            }

            $commentForm = $form->createView();

            $comments = $this->entityManager->getRepository(Comment::class)
                ->findBy(['post' => $post], ['createdAt' => 'ASC']);

            $commentCount = count($comments);
        }

        return $this->render('home/index.html.twig', array_merge($context, [
            'headerData' => $headerData,
            'pageTitle' => $this->pageTitle,
            'isHomeRoute' => $isHomeRoute,
            'displayList' => $displayList,
            'eventCalendar' => $eventCalendar,
            'postsWithUrls' => $postsWithUrls,
            'order' => $order,
            'adhesionUrl' => $adhesionUrl,
            'backToList' => $id ? true : false,
            'commentForm' => $commentForm,
            'comments' => $comments,
            'commentCount' => $commentCount,
            'id' => $id,
            'categories' => $this->categoriesRepository->findAll(),
            'allPostsWithUrls' => $allPostsWithUrls,
            'categoriesWithTabs' => $categoriesWithTabs,
        ]));
    }

    // Ordre chronologique des articles
    #[Route('/order/{order}', name: 'app_home_order_all', methods: ['POST'])]
    public function orderAllPosts(string $order = 'DESC'): Response
    {
        $request = $this->requestStack->getCurrentRequest();
        $posts = $this->postsRepository->findBy(
            ['status' => 1],
            ['posted_at' => $order]
        );

        $postsWithUrls = [];
        foreach ($posts as $post) {
            $params = ['id' => $post->getId()];
            $postUrl = $this->urlGenerator->generate('app_home_with_id', $params);
            $postsWithUrls[] = [
                'post' => $post,
                'url' => $postUrl
            ];
        }

        return $this->render('home/postslist.html.twig', [
            'postsWithUrls' => $postsWithUrls,
        ]);
    }

    // Ecrire un/des commentaire(s) pour un article
    // Afficher la liste des commentaire pour un article
    #[Route('/article/{id}/comment', name: 'app_comment_post', methods: ['POST'])]
    public function postComment(int $id, Request $request): Response
    {
        // Récupèrer le post
        $post = $this->postsRepository->find($id);
        if (!$post || !$post->isStatus()) {
            // Si le post n'existe pas ou n'est pas actif, rediriger vers l'accueil
            return $this->redirectToRoute('app_home');
        }

        // Formulaire des commentaires
        $comment = new Comment();
        $comment->setPost($post);
        $isEditorOrAdmin = $this->isGranted('ROLE_EDITOR') || $this->isGranted('ROLE_ADMIN');

        $form = $this->createForm(CommentType::class, $comment, [
            'is_editor_or_admin' => $isEditorOrAdmin,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user) {
                $comment->setUser($user);
            }
            $this->entityManager->persist($comment);

            // Mettre à jour le compteur des commentaires
            $this->entityManager->persist($post);

            $this->entityManager->flush();

            // Rediriger vers la page de l'article sélectionné
            return $this->redirectToRoute('app_home_with_id', ['id' => $id]);
        }

        // En cas d'erreur, rediriger
        return $this->redirectToRoute('app_home_with_id', ['id' => $id]);
    }
    // ===========================================
    // MÉTHODES PRIVÉES UTILITAIRES
    // ===========================================

    /**
     * Valide le paramètre d'ordre (ASC ou DESC)
     */
    private function validateOrder(string $order): string
    {
        $order = strtoupper($order);
        return in_array($order, ['ASC', 'DESC']) ? $order : 'DESC';
    }
    /**
     * Valide l'id de catégorie (doit être un nombre)
     */
    private function validateCategoryId(?string $categoryId): ?int
    {
        if ($categoryId === null || !ctype_digit($categoryId)) {
            return null;
        }
        return (int) $categoryId;
    }
}
