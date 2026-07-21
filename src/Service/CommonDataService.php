<?php

namespace App\Service;
// CommonDataService récupère et prépare des données de l'en-tête (logo, bannière, mantra,...)

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Repository\AssociationRepository;
use App\Repository\CategoriesRepository;
use App\Repository\PagesRepository;
use App\Repository\PostsRepository;
use App\Repository\TabsRepository;
use Psr\Log\LoggerInterface;

class CommonDataService
{
    public function __construct(
        private readonly PostsRepository $postsRepository,
        private readonly TabsRepository $tabsRepository,
        private readonly PagesRepository $pagesRepository,
        private readonly CategoriesRepository $categoriesRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private TextAnalyzerService $textAnalyzerService,
        private AssociationRepository $assoRepo,
        private string $kernelProjectDir
    ) {}

    /**
     * Récupère toutes les données du header prêtes à être passées au template
     * (avec le formatage du mantra, logo, banner, etc.)
     */
    public function getFullHeaderData(): array
    {
        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $assoId = $firstAssociation ? $firstAssociation->getId() : 1;

        $headerData = $this->getHeaderData($assoId);

        // Conversion sécurisée de la description
        $headerData['assoDescription'] = is_array($headerData['assoDescription'] ?? null)
            ? implode(' ', array_filter($headerData['assoDescription'], 'is_scalar'))
            : ($headerData['assoDescription'] ?? 'Description non disponible');

        $logoPath = $this->getLogoPath($headerData['assoLogo'] ?? '');
        $bannerFileName = $headerData['assoBanner'] ?? null;
        $bannerPath = $this->getBannerPath($bannerFileName);
        $formattedMantra = $this->getFormattedMantra($headerData['assoMantra'] ?? []);

        // Sécurise le format du mantra
        if (is_string($formattedMantra)) {
            $formattedMantra = ['line1' => $formattedMantra, 'line2' => ''];
        } elseif (!is_array($formattedMantra) || !isset($formattedMantra['line1'])) {
            $formattedMantra = ['line1' => 'Mantra non défini', 'line2' => ''];
        }

        return [
            'headerData' => $headerData,
            'logoPath' => $logoPath,
            'bannerPath' => $bannerPath,
            'formattedMantra' => $formattedMantra,
        ];
    }

    /**
     * Récupérer les données de l'association en fonction de l'ID fourni
     */
    public function getHeaderData(int $id): array
    {
        $asso = $this->assoRepo->findOneBy(['id' => $id]);

        if (!$asso) {
            return $this->getDefaultHeaderData();
        }

        // Récupérer le mantra
        $rawMantra = $asso->getMantra();
        $assoMantra = empty($rawMantra) ? ["Choisir un mantra"] : [$rawMantra];

        // Traiter le mantra
        $mantraText = $assoMantra[0];
        if ($this->textAnalyzerService->getWordCount($mantraText) > 4 && !str_contains($mantraText, ",")) {
            $assoMantra = $this->textAnalyzerService->splitTextByWordCount($mantraText, 4);
        } else {
            $assoMantra = $this->textAnalyzerService->splitAtComma($mantraText);
        }

        // Récupérer les autres champs
        $assoBanner = $this->decodeOrDefault($asso->getBanner(), null);
        $assoLogo = $this->decodeOrDefault($asso->getLogo(), null);
        $assoAddress = $this->decodeOrDefault($asso->getAddress(), null);
        $assoDescription = $this->decodeOrDefault($asso->getDescription(), null);
        $assoEmail = $this->decodeOrDefault($asso->getEmail(), null);
        $assoPhone = $this->decodeOrDefault($asso->getMobile(), null);
        $assoLinks = $this->decodeOrDefaultLinks($asso->getLinks());

        // Si assoLinks est vide, ajouter une URL par défaut (Bouton 'J'adhère')
        if (empty($assoLinks)) {
            $assoLinks = [['url' => '/association/adhesion']];
        }

        return [
            'assoMantra' => $assoMantra,
            'assoBanner' => $assoBanner,
            'assoLogo' => $assoLogo,
            'assoAddress' => $assoAddress,
            'assoDescription' => $assoDescription,
            'assoEmail' => $assoEmail,
            'assoPhone' => $assoPhone,
            'assoLinks' => $assoLinks ?? [['url' => '/association/adhesion']],
        ];
    }

    /**
     * Récupère les données communes (catégories, articles pour les sous-onglets)
     */
    public function getCommonData(): array
    {
        try {
            $allPosts = $this->postsRepository->findBy(['status' => 1], ['posted_at' => 'DESC']);
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération tous articles: ' . $e->getMessage());
            $allPosts = [];
        }

        $allPostsWithUrls = $this->buildPostsWithUrls($allPosts);

        $categories = $this->categoriesRepository->findAll();
        $categoriesWithTabs = array_map(fn($cat) => [
            'category' => $cat,
            'tabs' => $this->tabsRepository->findBy(['category' => $cat]),
        ], $categories);

        try {
            $pages = $this->pagesRepository->findAll();
        } catch (\Exception $e) {
            $this->logger->error('Erreur récupération pages: ' . $e->getMessage());
            $pages = [];
        }

        return [
            'categories' => $categories,
            'categoriesWithTabs' => $categoriesWithTabs,
            'allPostsWithUrls' => $allPostsWithUrls,
            'pages' => $pages,
            'isHomeRoute' => true,
            'adhesionUrl' => 'https://www.helloasso.com/associations/le-reseau-des-semeurs-de-jardins/adhesions/adhesion-annuelle-au-reseau-des-semeurs-de-jardins-2026',
        ];
    }

    /**
     * Génère les URLs pour une liste de posts
     */
    public function buildPostsWithUrls(array $posts, string $routeName = 'app_home_with_slug'): array
    {
        $allPosts = array_filter($posts, fn($post) => !empty($post->getSlug()));

        return array_map(function ($post) use ($routeName) {
            $slug = $post->getSlug();
            return [
                'post' => $post,
                'url' => $this->urlGenerator->generate($routeName, [
                    'slug' => $slug,
                ]),
            ];
        }, $allPosts);
    }


    /**
     * Retourner les données par défaut pour l'en-tête
     */
    public function getDefaultHeaderData(): array
    {
        return [
            'assoMantra' => ["Choisir un mantra"],
            'assoBanner' => null,
            'assoLogo' => null,
            'assoAddress' => null,
            'assoDescription' => null,
            'assoEmail' => null,
            'assoPhone' => null,
            'assoLinks' => [['url' => '/association/adhesion']],
        ];
    }

    /**
     * Récupérer le chemin du logo
     */
    public function getLogoPath(?string $assoLogo = null): string
    {
        $defaultLogoPath = 'uploads/profiles/SDJ/logo/logo_SDJ.png';
        $fallbackLogoPath = 'img/icons/logo_SDJ.png';

        if ($assoLogo && file_exists($this->kernelProjectDir . '/public/' . $assoLogo)) {
            return $assoLogo;
        }
        if (file_exists($this->kernelProjectDir . '/public/' . $defaultLogoPath)) {
            return $defaultLogoPath;
        }
        return $fallbackLogoPath;
    }

    /**
     * Récupérer le chemin de la bannière avec vérification
     */
    public function getBannerPath(?string $bannerFileName): string
    {
        $defaultBannerPath = 'uploads/profiles/SDJ/banner/_AJA0034.jpg';

        if (!$bannerFileName) {
            return $defaultBannerPath;
        }

        $bannerPath = 'uploads/profiles/SDJ/banner/' . $bannerFileName;
        $fullPath = $this->kernelProjectDir . '/public/' . $bannerPath;

        if (file_exists($fullPath)) {
            return $bannerPath;
        } else {
            return $defaultBannerPath;
        }
    }

    /**
     * Formater le mantra pour l'affichage
     */
    public function getFormattedMantra(array $assoMantra): array
    {
        return [
            'line1' => is_array($assoMantra) && isset($assoMantra[0]) ? $assoMantra[0] : "Mantra non défini",
            'line2' => is_array($assoMantra) && isset($assoMantra[1]) ? $assoMantra[1] : "-",
        ];
    }

    private function decodeOrDefault($value, $default)
    {
        if (empty($value)) {
            return $default;
        }
        return Utils::decode($value);
    }

    private function decodeOrDefaultLinks($links)
    {
        if (!$links) {
            return [];
        }
        $linksArray = $links->toArray();
        $decodedLinks = [];
        foreach ($linksArray as $link) {
            $decodedLinks[] = ['url' => Utils::decode($link->getUrl())];
        }
        return $decodedLinks;
    }
}
