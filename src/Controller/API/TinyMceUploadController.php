<?php

namespace App\Controller\API;

use Psr\Log\LoggerInterface;
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
    const MAX_IMAGE_SIZE = 10000000; // 10 Mo
    const MAX_FILE_SIZE = 50000000; // 50 Mo

    private $logger;

    public function __construct(
        private readonly ParameterBagInterface $params,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    // Images
    #[Route('/api/tinymce-upload/image', name: 'api_tinymce_upload_image', methods: ['POST'])]
    public function uploadImage(Request $request): Response
    {
        $file = $request->files->get("file");

        if (!$file) {
            $this->logger->warning('Tentative d\'upload d\'image sans fichier');
            return new JsonResponse(['error' => 'Aucun fichier envoyé.'], 400);
        }

        // Vérifier la taille
        if ($file->getSize() > self::MAX_IMAGE_SIZE) {
            $this->logger->warning('Tentative d\'upload d\'une image trop volumineuse: {size} (max: {max})', [
                'size' => $file->getSize(),
                'max' => self::MAX_IMAGE_SIZE
            ]);
            return new JsonResponse(['error' => 'L\'image est trop volumineuse. Taille maximale : 10 Mo.'], 400);
        }

        // Vérifier le type MIME et l'extension
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($mimeType, $allowedMimeTypes) || !in_array($extension, $allowedExtensions)) {
            $this->logger->warning('Tentative d\'upload d\'un fichier non-image: {mimeType}, extension: {extension}', [
                'mimeType' => $mimeType,
                'extension' => $extension
            ]);
            return new JsonResponse(['error' => 'Seules les images (JPEG, PNG, GIF, WebP) sont autorisées.'], 400);
        }

        // Générer un nom de fichier sécurisé
        $directory = $this->params->get('kernel.project_dir') . '/public/uploads/images';
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = uniqid('img_', true) . '.' . $extension;
        $file->move($directory, $fileName);

        $fileUrl = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'uploads/images/' . $fileName;
        return new JsonResponse(['location' => $fileUrl]);
    }

    // PDF : URL absolue + compatible PDF.js  ==> PDF.js à refaire...
    #[Route('/api/tinymce-upload/file', name: 'api_tinymce_upload_file', methods: ['POST'])]
    public function uploadFile(Request $request): Response
    {
        $file = $request->files->get("file");

        if (!$file) {
            $this->logger->warning('Tentative d\'upload de fichier sans fichier');
            return new JsonResponse(['error' => 'Aucun fichier envoyé.'], 400);
        }

        // Vérifier la taille
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            $this->logger->warning('Tentative d\'upload d\'un fichier PDF trop volumineux: {size}', ['size' => $file->getSize()]);
            return new JsonResponse(['error' => 'Le fichier est trop volumineux. Taille maximale : 50 Mo.'], 400);
        }

        // Vérifier que c'est un pdf + le type MIME + l'extension
        if ($file->getMimeType() !== "application/pdf" || strtolower($file->getClientOriginalExtension()) !== 'pdf') {
            $this->logger->warning('Tentative d\'upload d\'un fichier non-PDF: {mimeType}, extension: {extension}', [
                'mimeType' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension()
            ]);
            return new JsonResponse(['error' => 'Seuls les fichiers PDF sont autorisés.'], 400);
        }

        // Générer un nom de fichier sécurisé
        $directory = $this->params->get('kernel.project_dir') . '/public/uploads/files';
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = uniqid('file_', true) . '.pdf';
        $file->move($directory, $fileName);

        // URL absolue pour le PDF
        $fileUrl = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL) . 'uploads/files/' . $fileName;

        // Retourner un lien vers le PDF
        return new JsonResponse([
            'location' => $fileUrl,
            'html' => '<a href="' . $fileUrl . '" target="_blank" class="pdf-link">' . $file->getClientOriginalName() . '</a>'
        ]);
    }
}
