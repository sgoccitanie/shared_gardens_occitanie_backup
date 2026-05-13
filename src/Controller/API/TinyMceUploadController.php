<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TinyMceUploadController extends AbstractController
{
    const MAX_FILESIZE = 500000000; // 500 MB

    public function __construct(private readonly ParameterBagInterface $params) {}

    // Pour les images
    #[Route('/api/tinymce-upload/image', name: 'api_tinymce_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): Response
    {
        $file = $request->files->get("file");

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier envoyé.'], 400);
        }

        if ($file->getSize() > self::MAX_FILESIZE) {
            return new JsonResponse(['error' => 'Le fichier est trop volumineux. Taille maximale : ' . (self::MAX_FILESIZE / 1000000) . ' Mo.'], 400);
        }

        if (!str_starts_with($file->getMimeType(), "image/")) {
            return new JsonResponse(['error' => 'Le fichier n\'est pas une image valide.'], 400);
        }

        $extension = $file->guessExtension();
        $directory = $this->params->get('kernel.project_dir') . '/public/uploads/images';

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = uniqid() . '.' . $extension;
        $file->move($directory, $fileName);

        $fileUrl = $this->generateUrl('app_home', [], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL) . 'uploads/images/' . $fileName;
        return new JsonResponse(['location' => $fileUrl]);
    }

    // Pour les PDF : URL absolue + compatible PDF.js
    #[Route('/api/tinymce-upload/file', name: 'api_tinymce_upload_file', methods: ['POST'])]
    public function uploadFile(Request $request): Response
    {
        $file = $request->files->get("file");

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier envoyé.'], 400);
        }

        if ($file->getSize() > self::MAX_FILESIZE) {
            return new JsonResponse(['error' => 'Le fichier est trop volumineux.'], 400);
        }

        // Vérifier que c'est un PDF
        if ($file->getMimeType() !== "application/pdf") {
            return new JsonResponse(['error' => 'Seuls les fichiers PDF sont autorisés.'], 400);
        }

        $extension = $file->guessExtension();
        $directory = $this->params->get('kernel.project_dir') . '/public/uploads/files';

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = uniqid() . '.' . $extension;
        $file->move($directory, $fileName);

        // URL absolue pour le PDF
        $fileUrl = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'uploads/files/' . $fileName;

        // Retourner un lien vers le PDF (au lieu d'un iframe avec PDF.js)
        return new JsonResponse([
            'location' => $fileUrl,
            'html' => '<a href="' . $fileUrl . '" target="_blank" class="pdf-link">' . $file->getClientOriginalName() . '</a>'
        ]);
    }
}
