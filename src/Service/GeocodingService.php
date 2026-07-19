<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/api/addresses', name: 'app_api_addresses')]
class GeocodingService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route('/', name: 'app_addresses')]
    public function geocode(string $address): ?array
    {
        try {
            // Appel sur l'API avec HTTP
            $response = $this->httpClient->request('GET', 'https://api-adresse.data.gouv.fr/search/', [
                'query' => [
                    'q' => $address,
                    'limit' => 1,
                ],
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            if (empty($data['features'])) {
                return null;
            }

            $coordinates = $data['features'][0]['geometry']['coordinates'];
            $postalCode = $data['features'][0]['properties']['postcode'] ?? null;
            $area = $data['features'][0]['properties']['context'] ?? null;

            if ($area) {
                $areaPart = explode(',', $area);
                if (count($areaPart) >= 2) {
                    $dept = trim($areaPart[1]);
                    $region = trim($areaPart[2]);
                }
            }

            return [
                'latitude' => $coordinates[1],
                'longitude' => $coordinates[0],
                'postalCode' => $postalCode,
                'department' => $dept ?? null,
                'region' => $region ?? null,
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur géocodage: ' . $e->getMessage(), [
                'address' => $address,
            ]);
            return null;
        }
    }
}
