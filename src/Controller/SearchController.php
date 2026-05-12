<?php

namespace App\Controller;

use Google\Client as Google_Client;
use Google\Service\Drive;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    public function __construct(private readonly ParameterBagInterface $params) {}

    #[Route('/resources', name: 'app_resources', priority: 10)]
    public function resources(Request $request): Response
    {
        $credentialsPath = $this->params->get('google_application_credentials');
        $files = [];
        $message = null;

        // Récupérer le mot-clé tapé
        $query = trim($request->query->get('q', ''));

        if ($credentialsPath && file_exists($credentialsPath)) {
            try {
                $client = new Google_Client();
                $client->setAuthConfig($credentialsPath);
                $client->setScopes(Drive::DRIVE_READONLY);
                $client->setSubject('rsj-23@rsj2025.iam.gserviceaccount.com');

                if ($_ENV['APP_ENV'] === 'dev') {
                    $guzzleClient = new \GuzzleHttp\Client([
                        'curl' => [CURLOPT_SSL_VERIFYPEER => false]
                    ]);
                    $client->setHttpClient($guzzleClient);
                }

                $service = new Drive($client);

                // Requête

                // Dossiers autorisés
                $baseQuery = "(
                    '1sTXBydEI27J0mkwM-A50jXKRLpCsD4vI' in parents 
                    or 
                    '1-HPm2j0bllynbUOkOj4xe6AhH0KMt9dQ' in parents
                )";

                // Toujours exclure la corbeille
                $baseQuery .= " and trashed = false";

                // Recherche par mot -Insensible à la casse
                if (!empty($query)) {

                    // Sécurisation
                    $safeQuery = addslashes($query);

                    // Syntaxe Google Drive
                    $baseQuery .= " and name contains '" . $safeQuery . "'";
                }

                // Execution
                $response = $service->files->listFiles([
                    'pageSize' => 50,
                    'fields' => 'files(id, name, description, webViewLink, webContentLink, parents, mimeType)',
                    'q' => $baseQuery,
                    'orderBy' => 'name'
                ]);

                $files = $response->getFiles();

                // Si aucun résultat
                if (!empty($query) && count($files) === 0) {
                    $message = "Aucun résultat pour : " . $query;
                }
            } catch (\Exception $e) {
                $message = 'Erreur Drive : ' . $e->getMessage();
            }
        } else {
            $message = 'Fichier JSON manquant.';
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
