<?php

namespace App\Controller\API;

use Google\Client as Google_Client;
use Google\Service\Drive;
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
    private ?Drive $service = null;
    private ?string $error = null;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly LoggerInterface $logger,
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
        $files = [];
        $message = null;
        $query = trim($request->query->get('q', ''));

        // Si le service n'est pas disponible
        if ($this->service === null) {
            return $this->render('search/resources.html.twig', [
                'files' => [],
                'message' => $this->error,
                'query' => $query,
            ]);
        }

        // Validation du mot-clé
        if (!empty($query)) {
            if (strlen($query) < 2 || strlen($query) > 100) {
                return $this->render('search/resources.html.twig', [
                    'files' => [],
                    'message' => 'Le mot-clé doit faire entre 2 et 100 caractères.',
                    'query' => $query,
                ]);
            }
        }

        // Rate limiting (par IP)
        $limiter = $driveSearchLimiter->create($request->getClientIp());
        if (!$limiter->consume(1)->isAccepted()) {
            $this->logger->warning('Rate limit atteint pour search: {ip}', ['ip' => $request->getClientIp()]);
            return $this->render('search/resources.html.twig', [
                'files' => [],
                'message' => 'Trop de recherches. Veuillez patienter quelques minutes.',
                'query' => $query,
            ]);
        }

        try {
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

            $response = $this->service->files->listFiles([
                'pageSize' => 50,
                'fields' => 'files(id, name, description, webViewLink, webContentLink, parents, mimeType)',
                'q' => $baseQuery,
                'orderBy' => 'name',
            ]);

            $files = $response->getFiles();

            if (!empty($query) && count($files) === 0) {
                $message = "Aucun résultat pour : " . htmlspecialchars($query);
            }
        } catch (\Exception $e) {
            $this->logger->error('Google Drive search error: ' . $e->getMessage(), [
                'query' => $query,
            ]);
            $message = 'Service temporairement indisponible. Veuillez réessayer.';
        }

        return $this->render('search/resources.html.twig', [
            'files' => $files,
            'message' => $message,
            'query' => $query,
        ]);
    }

    #[Route('/search', name: 'app_search')]
    public function search(): Response
    {
        return $this->render('search/index.html.twig');
    }
}
