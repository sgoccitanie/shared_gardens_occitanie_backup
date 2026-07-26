<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Posts;
use App\Form\CommentType;
use App\Repository\PostsRepository;
use App\Repository\CategoriesRepository;
use App\Repository\PresentationRepository;
use App\Service\CommonDataService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class HomeController extends AbstractController
{
    private const STATIC_SLUGS = [
        'rgpd',
        'mentions-legales',
        'politique-de-confidentialite',
        'cookies',
    ];

    public function __construct(
        private readonly CommonDataService $commonDataService,
        private readonly PostsRepository $postsRepository,
        private readonly CategoriesRepository $categoriesRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly PresentationRepository $presentationRepository,
        #[Autowire(service: 'limiter.comment_form')]
        private readonly RateLimiterFactory $commentFormLimiter,
        
    ) {}

    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('home/presentation.html.twig', [
            'headerData' => $this->commonDataService->getFullHeaderData(),
            'presentationContent' => $this->getPresentationContent(),
        ]);
    }

    #[Route('/article/{slug}', name: 'app_home_with_slug', methods: ['GET', 'POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function index(string $slug, Request $request): Response
    {
        // Pages statiques : redirection vers leur route dédiée
        if (in_array($slug, self::STATIC_SLUGS, true)) {
            return $this->redirectToRoute('app_' . str_replace('-', '_', $slug));
        }

        $order = $this->validateOrder($request->query->get('order'));

        // Le slug correspond-il à un article ?
        $post = $this->findPostBySlug($slug);
        $categoryId = $request->query->get('category');

        if ($post !== null) {
            return $this->renderSinglePost($post, $request);
        }

        // Sinon, le slug correspond peut-être à une page : on liste ses articles
        return $this->renderTabPosts($slug, $order, $categoryId);
    }

    #[Route('/articles', name: 'app_articles', methods: ['GET'])]
    public function articles(Request $request): Response
    {
        $order = $this->validateOrder($request->query->get('order'));
        $categoryId = $this->validateCategoryId($request->query->get('category'));

        try {
            $qb = $this->postsRepository->createQueryBuilder('p')
                ->andWhere('p.status = 1')
                ->orderBy('p.posted_at', $order);

            if ($categoryId !== null) {
                $qb->select('DISTINCT p')
                    ->innerJoin('p.tabs', 't')
                    ->innerJoin('t.categories', 'c')
                    ->andWhere('c.id = :categoryId')
                    ->setParameter('categoryId', $categoryId);
            }

            $posts = $qb->getQuery()->getResult();
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération articles : ' . $e->getMessage());
            $posts = [];
        }

        return $this->renderList($posts, $order, null, (string) $categoryId);
    }

    #[Route('/order/{order}', name: 'app_home_order_all', methods: ['POST'], requirements: ['order' => 'ASC|DESC'])]
    public function orderAllPosts(string $order, Request $request): Response
    {
        $order = $this->validateOrder($order);

        try {
            $posts = $this->postsRepository->findBy(['status' => 1], ['posted_at' => $order]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur tri articles : ' . $e->getMessage());
            $posts = [];
        }

        return $this->render('home/postslist.html.twig', [
            'postsWithUrls' => $this->commonDataService->buildPostsWithUrls($posts),
        ]);
    }

    #[Route('/article/{slug}/comment', name: 'app_comment_post', methods: ['POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function postComment(string $slug, Request $request): Response
    {
        return $this->index($slug, $request);
    }

    // ===========================================
    // MÉTHODES PRIVÉES
    // ===========================================

    private function findPostBySlug(string $slug): ?Posts
    {
        try {
            return $this->postsRepository->findOneBy(['slug' => $slug, 'status' => 1]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération article par slug : ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Affiche un article unique, avec ses commentaires et le formulaire associé.
     */
    private function renderSinglePost(Posts $post, Request $request): Response
    {
        $comment = new Comment();
        $comment->setPost($post);
        $postSlug = $request->query->get('postSlug');
        $category = $request->query->get('category');

        $isEditorOrAdmin = $this->isGranted('ROLE_EDITOR') || $this->isGranted('ROLE_ADMIN');

        $commentForm = $this->createForm(CommentType::class, $comment, [
            'is_editor_or_admin' => $isEditorOrAdmin,
        ]);
        $commentForm->handleRequest($request);

        if ($commentForm->isSubmitted() && $commentForm->isValid()) {
            $limiter = $this->commentFormLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Trop de commentaires envoyés. Réessayez dans quelques minutes.');
                return $this->redirectToRoute('app_home_with_slug', ['slug' => $post->getSlug()]);
            }
            
            $user = $this->getUser();
            if ($user !== null) {
                $comment->setUser($user);
                if ($isEditorOrAdmin) {
                    // Use the user identifier (username/email) instead of non-existent getLogin()
                    if (method_exists($user, 'getUserIdentifier')) {
                        $comment->setPseudo($user->getUserIdentifier());
                    } elseif (method_exists($user, 'getUsername')) {
                        $comment->setPseudo($user->getUsername());
                    } else {
                        // Fallback to casting user to string if possible
                        $comment->setPseudo((string) $user);
                    }
                }
            }

            try {
                $this->entityManager->persist($comment);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_home_with_slug', ['slug' => $post->getSlug()]);
            } catch (\Exception $e) {
                $this->logger->error('Erreur sauvegarde commentaire : ' . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de l\'enregistrement du commentaire.');
            }
        }

        // Recherche les commentaires approuvés et rattachés au post
        $comments = $this->entityManager->getRepository(Comment::class)
            ->findBy(['post' => $post, 'parent' => null, 'isApproved' => true], ['createdAt' => 'ASC']);

        return $this->render('home/index.html.twig', [
            'postsWithUrls' => $this->commonDataService->buildPostsWithUrls([$post]),
            'categoryPost' => $this->findCategoryForPost($post),
            'displayList' => false,
            'commentForm' => $commentForm->createView(),
            'comments' => $comments,
            'commentCount' => count($comments),
            'slug' => $post->getSlug(),            
            'postSlug' => $postSlug,
            'categoryId' => $category,
            'backToList' => true,
            'eventCalendar' => false,
            'order' => 'DESC',
        ]);
    }

    /**
     * Affiche la liste des articles rattachés à une page (tab).
     */
    private function renderTabPosts(string $slug, string $order, ?string $categoryId): Response
    {
        $tabPost = true;

        try {
            $posts = $this->postsRepository->createQueryBuilder('p')
                ->select('DISTINCT p')
                ->innerJoin('p.tabs', 't')
                ->andWhere('t.slug = :slug')
                ->andWhere('p.status = 1')
                ->setParameter('slug', $slug)
                ->orderBy('p.posted_at', $order)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération articles par page : ' . $e->getMessage());
            $posts = [];
        }

        return $this->renderList($posts, $order, $slug, $categoryId, $tabPost);
    }

    /**
     * Rendu commun des pages de listing.
     *
     * @param Posts[] $posts
     */
    private function renderList(array $posts, string $order, ?string $slug, ?string $categoryId, ?bool $tabPost = false): Response
    {
        if(!empty($categoryId) && $categoryId !== null){
            $category = $this->categoriesRepository->find($categoryId);
        }

        return $this->render('home/index.html.twig', [
            'postsWithUrls' => $this->commonDataService->buildPostsWithUrls($posts),
            'categoryPost' => null,
            'displayList' => true,
            'commentForm' => null,
            'comments' => [],
            'commentCount' => 0,            
            'postSlug' => $slug,
            'slug' => $slug,
            'categoryId' => $categoryId ?? null,
            'category' => $category ?? null,
            'backToList' => false,
            'eventCalendar' => false,
            'order' => $order,
            'tabPost' => $tabPost,
        ]);
    }

    /**
     * Récupère la première catégorie de l'article, via ses pages.
     */
    private function findCategoryForPost(Posts $post): ?object
    {
        foreach ($post->getTabs() as $tab) {
            foreach ($tab->getCategories() as $category) {
                return $category;
            }
        }

        return null;
    }

    private function getPresentationContent(): string
    {
        $fallback = '
            <h2 class="mb-4 color-green">Bienvenue sur notre site</h2>
            <p class="lead text-center mb-4">
                Notre association met en réseau les différents jardins collectifs présents ou en devenir du Languedoc Roussillon.
            </p>
            <p class="mb-4">
                En 2020, nous avons lancé un projet ambitieux : planter des bosquets fruitiers sur l\'espace public de Montpellier !
            </p>
        ';

        try {
            $presentation = $this->presentationRepository->findOneBy([], ['id' => 'ASC']);

            return $presentation?->getContent() ?: $fallback;
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération présentation : ' . $e->getMessage());

            return $fallback;
        }
    }

    private function validateOrder(?string $order): string
    {
        $order = strtoupper((string) $order);

        return in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';
    }

    private function validateCategoryId(?string $categoryId): ?int
    {
        if ($categoryId === null || !ctype_digit($categoryId)) {
            return null;
        }

        return (int) $categoryId;
    }
}