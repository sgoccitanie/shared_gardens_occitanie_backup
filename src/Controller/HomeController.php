<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\PostsRepository;
use App\Repository\PresentationRepository;
use App\Service\CommonDataService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly CommonDataService $commonDataService,
        private readonly PostsRepository $postsRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly PresentationRepository $presentationRepository,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Page d'accueil (présentation)
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {

        // Récupérer le contenu de la présentation
        try {
            $presentation = $this->presentationRepository->findOneBy([], ['id' => 'ASC']);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération présentation: ' . $e->getMessage());
            $presentation = null;
        }

        $presentationContent = $presentation ? $presentation->getContent() : '
            <h2 class="mb-4 color-green">Bienvenue sur notre site</h2>
            <p class="lead text-center mb-4">
                Notre association met en réseau les différents jardins collectifs présents ou en devenir du Languedoc Roussillon.
            </p>
        ';

        return $this->render('home/presentation.html.twig', [
            'presentationContent' => $presentationContent,
            'pageTitle' => 'Accueil | Réseau des Semeurs de Jardins',
        ]);
    }

    /**
     * Liste de tous les articles
     */
    #[Route('/articles', name: 'app_articles', methods: ['GET'])]
    public function articles(Request $request): Response
    {
        // Validation des paramètres
        $order = $this->validateOrder($request->query->get('order', 'DESC'));
        $categoryId = $this->validateCategoryId($request->query->get('category'));

        try {
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
                $posts = $this->postsRepository->findBy(
                    ['status' => 1],
                    ['posted_at' => $order]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération articles: ' . $e->getMessage());
            $posts = [];
        }

        $postsWithUrls = $this->commonDataService->buildPostsWithUrls($posts);

        return $this->render('home/index.html.twig', [
            'pageTitle' => 'Tous les articles',
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
        ]);
    }

    /**
     * Liste des articles par catégorie/tab (via slug)
     */
    #[Route('/articles/{slug}', name: 'app_articles_by_slug', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function articlesBySlug(string $slug, Request $request): Response
    {
        $order = $this->validateOrder($request->query->get('order', 'DESC'));

        try {
            $posts = $this->postsRepository->createQueryBuilder('p')
                ->addSelect('t')
                ->innerJoin('p.tab', 't')
                ->andWhere('t.slug = :slug')
                ->setParameter('slug', $slug)
                ->andWhere('p.status = 1')
                ->orderBy('p.posted_at', $order)
                ->getQuery()
                ->getResult();
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération articles par slug: ' . $e->getMessage());
            $posts = [];
        }

        $postsWithUrls = $this->commonDataService->buildPostsWithUrls($posts);

        return $this->render('home/index.html.twig', [
            'pageTitle' => 'Articles',
            'postsWithUrls' => $postsWithUrls,
            'displayList' => true,
            'order' => $order,
            'backToList' => false,
            'commentForm' => null,
            'comments' => [],
            'commentCount' => 0,
            'id' => null,
        ]);
    }

    /**
     * Affichage d'un article unique via son slug
     */
    #[Route('/article/{slug}', name: 'app_post_show', methods: ['GET', 'POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function showPost(string $slug, Request $request): Response
    {
        try {
            $post = $this->postsRepository->findOneBy(['slug' => $slug, 'status' => 1]);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération article: ' . $e->getMessage());
            throw $this->createNotFoundException('Article introuvable');
        }

        if (!$post) {
            throw $this->createNotFoundException('Article introuvable');
        }

        // Gestion du formulaire de commentaire
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
                if ($isEditorOrAdmin) {
                    $comment->setPseudo((string) $user);
                }
            }

            try {
                $this->entityManager->persist($comment);
                $this->entityManager->flush();
                return $this->redirectToRoute('app_post_show', ['slug' => $slug]);
            } catch (\Exception $e) {
                $this->logger->error('Erreur sauvegarde commentaire: ' . $e->getMessage());
                $this->addFlash('error', 'Erreur lors de l\'enregistrement du commentaire.');
            }
        }

        $comments = $this->entityManager->getRepository(Comment::class)
            ->findBy(['post' => $post], ['createdAt' => 'ASC']);

        return $this->render('home/index.html.twig', [
            'pageTitle' => $post->getTitle() ?? 'Article',
            'postsWithUrls' => [['post' => $post, 'url' => $this->urlGenerator->generate('app_post_show', ['slug' => $slug])]],
            'displayList' => false,
            'eventCalendar' => false,
            'backToList' => true,
            'commentForm' => $form->createView(),
            'comments' => $comments,
            'commentCount' => count($comments),
            'id' => $post->getId(),
            'slug' => $post->getSlug(),
        ]);
    }

    /**
     * Tri chronologique des articles (utilisé en AJAX)
     */
    #[Route('/order/{order}', name: 'app_home_order_all', methods: ['POST'])]
    public function orderAllPosts(string $order = 'DESC'): Response
    {
        $order = $this->validateOrder($order);

        try {
            $posts = $this->postsRepository->findBy(
                ['status' => 1],
                ['posted_at' => $order]
            );
        } catch (\Exception $e) {
            $this->logger->error('Erreur tri articles: ' . $e->getMessage());
            $posts = [];
        }

        $postsWithUrls = $this->commonDataService->buildPostsWithUrls($posts);

        return $this->render('home/postslist.html.twig', [
            'postsWithUrls' => $postsWithUrls,
        ]);
    }

    /**
     * Ajout d'un commentaire (route alternative)
     */
    #[Route('/article/{slug}/comment', name: 'app_comment_post', methods: ['POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function postComment(string $slug, Request $request): Response
    {
        return $this->showPost($slug, $request);
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
