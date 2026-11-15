<?php

namespace App\Controller\API;

use Google\Client as Google_Client;
use Google\Service\Drive;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class SearchController extends AbstractController
{
    /** Nombre de ressources affichées par page */
    private const PER_PAGE = 12;
    /** Nombre maximum de fichiers récupérés sur Google Drive pour une recherche */
    private const MAX_RESULTS = 500;
    /** Durée de mise en cache d'une recherche (secondes) : changer de page ne réinterroge pas Google Drive */
    private const CACHE_TTL = 600;

    private ?Drive $service = null;
    private ?string $error = null;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly LoggerInterface $logger,
        private readonly CacheItemPoolInterface $cache,
    ) {
        $credentialsPath = $this->params->get('google_application_credentials');

        if (!$credentialsPath || !file_exists($credentialsPath)) {
            $this->error = 'Service Google Drive non disponible';
            $this->logger->error('Google Drive credentials introuvables');
            return;
        }

        try {
            $client = new Google_Client();
            $client->setApplicationName('semeursdejardins');
            $client->setScopes(Drive::DRIVE_READONLY);
            $client->setAuthConfig($credentialsPath);
            $client->setAccessType('offline');

            $this->service = new Drive($client);
        } catch (\Exception $e) {
            $this->logger->error('Google Drive init error: ' . $e->getMessage());
            $this->error = 'Service temporairement indisponible';
        }
    }

    #[Route('/resources', name: 'app_resources', priority: 10)]
    public function resources(
        Request $request,
        #[Autowire(service: 'limiter.drive_search')]
        RateLimiterFactory $driveSearchLimiter
    ): Response {
        $query = trim($request->query->get('q', ''));
        $page = max(1, $request->query->getInt('page', 1));

        // Si le service n'est pas disponible
        if ($this->service === null) {
            return $this->renderResources([], $this->error, $query);
        }

        // Validation du mot-clé
        if (!empty($query) && (strlen($query) < 2 || strlen($query) > 100)) {
            return $this->renderResources([], 'Le mot-clé doit faire entre 2 et 100 caractères.', $query);
        }

        // Les résultats d'une recherche sont gardés en cache : la pagination ne réinterroge pas Google Drive
        $cacheItem = $this->cache->getItem('drive_search_' . md5(mb_strtolower($query)));

        if ($cacheItem->isHit()) {
            $files = $cacheItem->get();
        } else {
            // Rate limiting (par IP), uniquement pour les recherches qui interrogent réellement Google Drive
            $limiter = $driveSearchLimiter->create($request->getClientIp());
            if (!$limiter->consume(1)->isAccepted()) {
                $this->logger->warning('Rate limit atteint pour search: {ip}', ['ip' => $request->getClientIp()]);

                return $this->renderResources([], 'Trop de recherches. Veuillez patienter quelques minutes.', $query);
            }

            try {
                $files = $this->fetchDriveFiles($query);
            } catch (\Exception $e) {
                $this->logger->error('Google Drive search error: ' . $e->getMessage(), [
                    'query' => $query,
                ]);

                return $this->renderResources([], 'Service temporairement indisponible. Veuillez réessayer.', $query);
            }

            $cacheItem->set($files)->expiresAfter(self::CACHE_TTL);
            $this->cache->save($cacheItem);
        }

        $message = null;
        if (!empty($query) && count($files) === 0) {
            $message = "Aucun résultat pour : " . $query;
        }

        return $this->renderResources($files, $message, $query, $page);
    }

    /**
     * Récupère tous les fichiers correspondant à la recherche (toutes les pages Google Drive, dans la limite de MAX_RESULTS).
     *
     * @return array<int, array{id: ?string, name: ?string, description: ?string, webViewLink: ?string, webContentLink: ?string, parents: array, mimeType: ?string}>
     */
    private function fetchDriveFiles(string $query): array
    {
        // Récupération des IDs de dossiers depuis les variables d'environnement
        $folderOneId = $this->params->get('google_drive_folder_rmorez');
        $folderTwoId = $this->params->get('google_drive_folder_rsj');

        $baseQuery = "(
            '" . $folderOneId . "' in parents 
            or 
            '" . $folderTwoId . "' in parents
        )";
        $baseQuery .= " and trashed = false";

        // Recherche par mot-clé avec échappement
        if (!empty($query)) {
            $safeQuery = addslashes($query);
            $baseQuery .= " and name contains '" . $safeQuery . "'";
        }

        $files = [];
        $pageToken = null;

        do {
            $params = [
                'pageSize' => 100,
                'fields' => 'nextPageToken, files(id, name, description, webViewLink, webContentLink, parents, mimeType)',
                'q' => $baseQuery,
                'orderBy' => 'name',
            ];
            if ($pageToken !== null) {
                $params['pageToken'] = $pageToken;
            }

            $response = $this->service->files->listFiles($params);

            // Tableaux simples : plus légers à mettre en cache que les objets Google
            foreach ($response->getFiles() as $file) {
                $files[] = [
                    'id' => $file->getId(),
                    'name' => $file->getName(),
                    'description' => $file->getDescription(),
                    'webViewLink' => $file->getWebViewLink(),
                    'webContentLink' => $file->getWebContentLink(),
                    'parents' => $file->getParents() ?? [],
                    'mimeType' => $file->getMimeType(),
                ];
            }

            $pageToken = $response->getNextPageToken();
        } while ($pageToken && count($files) < self::MAX_RESULTS);

        return array_slice($files, 0, self::MAX_RESULTS);
    }

    /**
     * Affiche la page des ressources avec la tranche de résultats correspondant à la page demandée.
     */
    private function renderResources(array $files, ?string $message, string $query, int $page = 1): Response
    {
        $total = count($files);
        $pageCount = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pageCount);

        return $this->render('search/resources.html.twig', [
            'files' => array_slice($files, ($page - 1) * self::PER_PAGE, self::PER_PAGE),
            'message' => $message,
            'query' => $query,
            'page' => $page,
            'pageCount' => $pageCount,
            'total' => $total,
            'folderMorezId' => $this->params->has('google_drive_folder_rmorez') ? $this->params->get('google_drive_folder_rmorez') : null,
        ]);
    }
}
