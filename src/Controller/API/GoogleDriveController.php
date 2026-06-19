<?php

namespace App\Controller\API;

use Google\Service\Drive as ServiceDrive;
use Google\Client;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GoogleDriveController extends AbstractController
{
    private ?ServiceDrive $service = null;
    private ?string $error = null;

    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly LoggerInterface $logger
    ) {
        $credentialsPath = $this->params->get('google_application_credentials');

        if (!$credentialsPath || !file_exists($credentialsPath)) {
            $this->error = 'Service Google Drive non disponible';
            $this->logger->error('Google Drive credentials introuvables');
            return;
        }

        try {
            $client = new Client();
            $client->setApplicationName('semeursdejardins');
            $client->setScopes('https://www.googleapis.com/auth/drive.readonly');
            $client->setAuthConfig($credentialsPath);
            $client->setAccessType('offline');

            $this->service = new ServiceDrive($client);
        } catch (\Exception $e) {
            $this->logger->error('Google Drive init error: ' . $e->getMessage());
            $this->error = 'Service temporairement indisponible';
        }
    }

    #[Route('/search', name: 'app_search')]
    public function index(): Response
    {
        return $this->render('search/index.html.twig');
    }

    #[Route('/search/drive/{page?}', name: 'app_search_drive', methods: ['GET', 'POST'])]
    public function drive(Request $request, ?string $keywords = null, ?int $page = null, ?string $order = null): Response
    {
        // check if the request is a POST request
        if ($request->isMethod('POST')) {
            // dd($request->request->get('keywords'));
            if ($request->request->get('keywords') != null) {
                $keywords = $request->request->get('keywords');
            }
            if ($request->request->get('order') != null) {
                $order = $request->request->get('order');
                // dd($order);
            }
        }
        if ($page == null) {
            $page = 0;
        }
        if ($order == null) {
            $order = 'all';
        }
        // R.Morez folder id
        $folderOneId = "'" . $this->params->get('google_drive_folder_rmorez') . "' in parents";
        // RSJ folder id
        $folderTwoId = "'" . $this->params->get('google_drive_folder_rsj') . "' in parents";
        $files = [];
        $message = null;
        $filesPages = [];
        $folderId = [];
        // dd($folderId);
        try {
            // dd($order);
            if ($order == 'all' || $order == null) {
                array_push($folderId, $folderOneId);
                array_push($folderId, $folderTwoId);
            } else if ($folderOneId == $order) {
                array_push($folderId, $folderOneId);
                // dump($folderId);
            } else if ($folderTwoId == $order) {
                array_push($folderId, $folderTwoId);
                // dump($folderId);
            }
            // dd($order, $folderId);

            if (count($folderId) > 1) {
                $option = implode(' or ', $folderId);
                $q = "(" . $option . ") and trashed = false and mimeType != 'application/vnd.google-apps.folder'";
            } else {
                $option = $folderId[0];
                $q = "(" . $option . ") and trashed = false and mimeType != 'application/vnd.google-apps.folder'";
            }
            // dd($option);
            $optParams = array(
                'corpora' => "user",
                'orderBy' => "modifiedTime desc",
                'pageSize' => 25,
                'fields' => "nextPageToken, files(id, name, description, size, createdTime, modifiedTime, webViewLink, thumbnailLink, webContentLink, parents)",
                'q' => $q,
                'includeItemsFromAllDrives' => 'false',
                'supportsAllDrives' => 'false'
            );

            if ($keywords !== null) {
                if (strlen($keywords) < 2 || strlen($keywords) > 100) {
                    $message = 'Le mot-clé doit faire entre 2 et 100 caractères';
                    return $this->render('search/drive.html.twig', [
                        'message' => $message,
                        'files' => $filesPages,
                        'page' => $page,
                        'order' => $order,
                    ]);
                }
                // Échappe les apostrophes en premier
                $safeKeywords = addslashes($keywords);

                // Puis on fait le split sur les espaces
                $keywordssplited = urlencode($safeKeywords);
                $keywordssplited = str_replace('%20', "' and fullText contains '", $keywordssplited);

                $optParams['q'] = "(" . $option . ") and trashed = false and mimeType != 'application/vnd.google-apps.folder' and fullText contains '" . $keywordssplited . "'";
            }
            $optParams['pageToken'] = null;
            $allFiles = [];

            do {
                try {
                    $results = $this->service->files->listFiles($optParams);
                    $files = $results->getFiles();

                    if (count($files) > 0) {
                        foreach ($files as $file) {
                            $allFiles[] = $file;
                        }
                        $filesPages[] = $files;
                    }

                    // Toujours mettre à jour le pageToken, même si pas de fichiers
                    $optParams['pageToken'] = $results->getNextPageToken();
                } catch (\Exception $e) {
                    $this->logger->error('Drive listFiles error: ' . $e->getMessage());
                    $message = 'Erreur lors de la recherche, veuillez réessayer.';
                    break; // sortir de la boucle en cas d'erreur
                }
            } while ($optParams['pageToken'] !== null);
            // si aucun fichier trouvé
            if (empty($allFiles)) {
                $message = 'Aucun résultat trouvé';
            }
        } catch (\Exception $e) {
            $this->logger->error('Drive search error: ' . $e->getMessage());
            $message = 'Erreur lors de la recherche, veuillez réessayer.';
        }
        if ($request->isMethod('POST')) {
            return $this->render('search/drive.html.twig', [
                'files' => $filesPages,
                'message' => $message,
                'page' => $page,
                'order' => $order,
            ]);
        } else {
            return $this->render('search/index.html.twig', [
                'files' => $filesPages,
                'message' => $message,
                'page' => $page,
                'order' => $order,
            ]);
        }
    }
}
