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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private readonly CommonDataService $commonDataService,
        private readonly PostsRepository $postsRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly LoggerInterface $logger,
        private readonly PresentationRepository $presentationRepository,
    ) {}

    #[Route('/article/{slug}', name: 'app_home_with_slug', methods: ['GET', 'POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(?string $slug = null, ?Request $request = null): Response
    {
        $posts = [];
        $post = null;
        $commentForm = null;
        $comments = [];
        $commentCount = 0;
        $order = 'ASC';
        if ($request) {
            $order = strtoupper($request->query->get('order'));
            if (!in_array($order, ['ASC', 'DESC'])) {
                $order = 'DESC';
            }
        }

        if ($slug) {
            // Vérifier si c'est une page statique
            $staticSlugs = ['rgpd', 'mentions-legales', 'politique-de-confidentialite', 'cookies', 'qui-sommes-nous'];
            if (in_array($slug, $staticSlugs)) {
                return $this->redirectToRoute('app_' . str_replace('-', '_', $slug));
            }
            $request = $this->requestStack->getCurrentRequest();
            $adhesionUrl = 'https://www.helloasso.com/associations/le-reseau-des-semeurs-de-jardins/adhesions/adhesion-annuelle-au-reseau-des-semeurs-de-jardins-2026';
            // Chercher d'abord si c'est le slug d'un article
            $post = $this->postsRepository->findOneBy(['slug' => $slug, 'status' => 1]);

            if ($post) {
                // C'est un article unique
                $posts = [$post];

                // Gestion du formulaire de commentaire
                $comment = new Comment();
                $comment->setPost($post);

                $isEditorOrAdmin = $this->isGranted('ROLE_EDITOR') || $this->isGranted('ROLE_ADMIN');

                $commentForm = $this->createForm(CommentType::class, $comment, [
                    'is_editor_or_admin' => $isEditorOrAdmin,
                ]);

                if ($request) {
                    $commentForm->handleRequest($request);

                    if ($commentForm->isSubmitted() && $commentForm->isValid()) {
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
                            return $this->redirectToRoute('app_home_with_id', ['slug' => $slug]);
                        } catch (\Exception $e) {
                            $this->logger->error('Erreur sauvegarde commentaire: ' . $e->getMessage());
                            $this->addFlash('error', 'Erreur lors de l\'enregistrement du commentaire.');
                        }
                    }
                }

                // Récupérer les commentaires
                $comments = $this->entityManager->getRepository(Comment::class)
                    ->findBy(['post' => $post], ['createdAt' => 'ASC']);
                $commentCount = count($comments);
            } else {
                // Sinon, c'est peut-être une page: afficher la liste des articles
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
            }
        }

        // Page présentation
        if ($slug === null) {
            $headerData = $this->commonDataService->getFullHeaderData();

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
            ]);
        }

        // Construction du tableau des posts avec URLs
        $postsWithUrls = $this->commonDataService->buildPostsWithUrls($posts);

        // Détermine si on affiche la liste ou un article unique
        $displayList = $post === null ? true : false;

        return $this->render('home/index.html.twig', [
            'postsWithUrls' => $postsWithUrls,
            'displayList' => $displayList,
            'commentForm' => $commentForm?->createView(),
            'comments' => $comments,
            'commentCount' => $commentCount,
            'slug' => $slug,
            'backToList' => $post !== null,
            'eventCalendar' => false,
            'order' => $order,
        ]);
    }

    #[Route('/articles', name: 'app_articles', methods: ['GET'])]
    public function articles(Request $request): Response
    {
        // Validation du paramètre order
        $order = strtoupper($request->query->get('order', 'DESC'));
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

        // Validation du paramètre category
        $categoryId = $request->query->get('category');
        if ($categoryId !== null && !ctype_digit($categoryId)) {
            $categoryId = null;
        }

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

        $postsWithUrls = $this->commonDataService->buildPostsWithUrls($posts, 'app_articles');

        return $this->render('home/index.html.twig', [
            'postsWithUrls' => $postsWithUrls,
            'displayList' => true,
            'order' => $order,
            'backToList' => false,
            'commentForm' => null,
            'comments' => [],
            'commentCount' => 0,
            'slug' => null,
            'eventCalendar' => false,
        ]);
    }

    #[Route('/order/{order}', name: 'app_home_order_all', methods: ['POST'])]
    public function orderAllPosts(string $order = 'DESC'): Response
    {
        $order = strtoupper($order);
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC';
        }

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

        return $this->render('home/index.html.twig', [
            'postsWithUrls' => $postsWithUrls,
        ]);
    }

    #[Route('/article/{slug}/comment', name: 'app_comment_post', methods: ['POST'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function postComment(string $slug, Request $request): Response
    {
        return $this->index($slug, $request);
    }
}
