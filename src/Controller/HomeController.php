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
use App\Repository\CommentRepository;
use App\Service\HeaderService;
use App\Service\PageLogicService;
use App\Repository\AssociationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HomeController extends AbstractController
{
    private $pageTitle = 'Accueil | Réseau des Semeurs de Jardins';

    public function __construct(
        private HeaderService $headerService,
        private PostsRepository $postsRepository,
        private TabsRepository $tabsRepository,
        private PagesRepository $pagesRepository,
        private PageLogicService $pageLogicService,
        private RequestStack $requestStack,
        private AssociationRepository $assoRepo,
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager,
        private CategoriesRepository $categoriesRepository,
        private AddressesRepository $addressesRepository,
        private PresentationRepository $presentationRepository
    ) {}

    // Carte des jardins
    #[Route('/map/jardins', name: 'app_jardins')]
    public function map(): Response
    {
        // Récupèrer les adresses avec des coordonnées valides
        $addresses = $this->addressesRepository->findValidAddresses();

        return $this->render('home/map.html.twig', [
            'addresses' => $addresses,
        ]);
    }

    // S'inscrire
    #[Route('/inscription', name: 'app_registration', methods: ['GET', 'POST'])]
    public function registration(): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);

        $request = $this->requestStack->getCurrentRequest();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    // Tous les articles
    #[Route('/articles', name: 'app_articles', methods: ['GET'])]
    public function articles(): Response
    {
        $request = $this->requestStack->getCurrentRequest();

        // lecture propre + validation stricte
        $order = strtoupper($request->query->get('order', 'DESC'));
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        // Récupération du filtre catégorie
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
            $posts = [];
        }

        // Rediriger vers l'article
        $postsWithUrls = [];
        foreach ($posts as $post) {
            $postsWithUrls[] = [
                'post' => $post,
                'url' => $this->urlGenerator->generate('app_home_with_id', [
                    'id' => $post->getId()
                ]),
            ];
        }

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


        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $assoId = $firstAssociation ? $firstAssociation->getId() : 1;

        $headerData = $this->headerService->getHeaderData($assoId);
        $headerData['assoDescription'] = is_array($headerData['assoDescription'] ?? null)
            ? implode(' ', $headerData['assoDescription'])
            : ($headerData['assoDescription'] ?? 'Description non disponible');

        $logoPath = $this->headerService->getLogoPath($headerData['assoLogo']);
        $bannerFileName = $headerData['assoBanner'] ?? null;
        $bannerPath = $this->headerService->getBannerPath($bannerFileName);
        $formattedMantra = $this->headerService->getFormattedMantra($headerData['assoMantra']);

        try {
            $pages = $this->pagesRepository->findAll();
        } catch (\Exception $e) {
            $pages = [];
        }

        return $this->render('home/index.html.twig', [
            'headerData' => $headerData,
            'pageTitle' => 'Tous les articles',
            'logoPath' => $logoPath,
            'bannerPath' => $bannerPath,
            'formattedMantra' => $formattedMantra,

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
            $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
            $assoId = $firstAssociation ? $firstAssociation->getId() : 1;
            $headerData = $this->headerService->getHeaderData($assoId);

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

            $formattedMantra = $this->headerService->getFormattedMantra($headerData['assoMantra'] ?? '');
            if (is_string($formattedMantra)) {
                $formattedMantra = ['line1' => $formattedMantra, 'line2' => ''];
            } elseif (!is_array($formattedMantra) || !isset($formattedMantra['line1'])) {
                $formattedMantra = ['line1' => 'Mantra non défini', 'line2' => ''];
            }

            $logoPath = $this->headerService->getLogoPath($headerData['assoLogo'] ?? '');
            $bannerFileName = $headerData['assoBanner'] ?? null;
            $bannerPath = $this->headerService->getBannerPath($bannerFileName);

            return $this->render('home/presentation.html.twig', [
                'headerData' => $headerData,
                'bannerPath' => $bannerPath,
                'formattedMantra' => $formattedMantra,
                'logoPath' => $logoPath,
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
        $assoId = $firstAssociation ? $firstAssociation->getId() : 1;
        $headerData = $this->headerService->getHeaderData($assoId);
        $headerData['assoDescription'] = is_array($headerData['assoDescription'] ?? null)
            ? implode(' ', $headerData['assoDescription'])
            : ($headerData['assoDescription'] ?? 'Description non disponible');
        $logoPath = $this->headerService->getLogoPath($headerData['assoLogo']);
        $bannerFileName = $headerData['assoBanner'] ?? null;
        $bannerPath = $this->headerService->getBannerPath($bannerFileName);
        $formattedMantra = $this->headerService->getFormattedMantra($headerData['assoMantra']);

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

            $form = $this->createForm(CommentType::class, $comment);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $user = $this->getUser();
                if ($user) {
                    $comment->setUser($user);
                }

                $this->entityManager->persist($comment);

                $currentCount = $post->getCommentCounter() ?? 0;
                $post->setCommentCounter($currentCount + 1);
                $this->entityManager->persist($post);

                $this->entityManager->flush();

                return $this->redirectToRoute('app_home_with_id', ['id' => $id]);
            }

            $commentForm = $form->createView();

            $comments = $this->entityManager->getRepository(Comment::class)->findBy(['post' => $post], ['createdAt' => 'ASC']);
            $commentCount = $post->getCommentCounter() ?? count($comments);
        }






        return $this->render('home/index.html.twig', array_merge($context, [
            'headerData' => $headerData,
            'pageTitle' => $this->pageTitle,
            'logoPath' => $logoPath,
            'bannerPath' => $bannerPath,
            'formattedMantra' => $formattedMantra,
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
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user) {
                $comment->setUser($user);
            }
            $this->entityManager->persist($comment);

            // Mettre à jour le compteur des commentaires
            $currentCount = $post->getCommentCounter() ?? 0;
            $post->setCommentCounter($currentCount + 1);
            $this->entityManager->persist($post);

            $this->entityManager->flush();

            // Rediriger vers la page de l'article sélectionné
            return $this->redirectToRoute('app_home_with_id', ['id' => $id]);
        }

        // En cas d'erreur, rediriger
        return $this->redirectToRoute('app_home_with_id', ['id' => $id]);
    }
}
