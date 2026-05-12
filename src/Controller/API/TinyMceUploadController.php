<?php

namespace App\Controller\API;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TinyMceUploadController extends AbstractController
{
    const MAX_FILESIZE = 500000000; // 500 MB

    public function __construct(private readonly ParameterBagInterface $params) {}

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

    #[Route('/api/tinymce-upload/file', name: 'api_tinymce_upload_file', methods: ['POST'])]
    public function uploadFile(Request $request): Response
    {
        $file = $request->files->get("file");

        if (!$file) {
            return new JsonResponse(['error' => 'Aucun fichier envoyé.'], 400);
        }

        if ($file->getSize() > self::MAX_FILESIZE) {
            return new JsonResponse(['error' => 'Le fichier est trop volumineux. Taille maximale : ' . (self::MAX_FILESIZE / 1000000) . ' Mo.'], 400);
        }

        if ($file->getMimeType() !== "application/pdf") {
            return new JsonResponse(['error' => 'Le fichier n\'est pas un PDF valide.'], 400);
        }

        $extension = $file->guessExtension();
        $directory = $this->params->get('kernel.project_dir') . '/public/uploads/files';

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $fileName = uniqid() . '.' . $extension;
        $file->move($directory, $fileName);

        $fileUrl = '/uploads/files/' . $fileName;

        return new JsonResponse(['location' => $fileUrl]);
    }
}
