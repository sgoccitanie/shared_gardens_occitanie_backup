<?php

namespace App\Service;

// HeaderService récupère et prépare des données de l'en-tête (logo, bannière, mantra,...)
// Le Controller appelle ce service quand nécessaire

use App\Repository\AssociationRepository;

class HeaderService
{
    public function __construct(
        private TextAnalyzerService $textAnalyzerService,
        private AssociationRepository $assoRepo,
        private string $kernelProjectDir
    ) {}

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
        $fallbackLogoPath = 'img/icons/logo_SDJ.png'; // Chemin de secours

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
