<?php
// Nettoyer le pdf pour faire disparaître sandbox="" et les imbrications à chaque nouveau pdf ajouté dans le contenu d'un post

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use App\Entity\Posts;

class PostContentListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'cleanPostContent',
            BeforeEntityUpdatedEvent::class => 'cleanPostContent',
        ];
    }

    public function cleanPostContent($event): void
    {
        $entity = $event->getEntityInstance();

        // Cibler uniquement l'entité Posts
        if (!$entity instanceof Posts) {
            return;
        }

        $content = $entity->getContent();
        if (empty($content)) {
            return;
        }

        // Nettoyer le HTML => supprimer sandbox="" et les imbrications (div à la volée...)
        $cleanedContent = $this->cleanPdfHtml($content);
        $entity->setContent($cleanedContent);
    }

    /**
     * Nettoyer le HTML pour :
     * 1. Supprimer sandbox="" dans les iframes
     * 2. Remplacer les iframes par des <object> (plus fiable pour les PDF)
     * 3. Supprimer les imbrications inutiles de div.pdf-embed
     */
    private function cleanPdfHtml(string $html): string
{
        // 1. FORCER Remplacer les placeholders par des <object> (plus fiable que les iframes)
        $html = preg_replace_callback(
        '/<div class="pdf-embed" data-pdf-url="([^"]+)"><div class="pdf-placeholder">[^<]*<\/div><\/div>/',
        function ($matches) {
            $pdfUrl = $matches[1];
            return '<div class="pdf-embed"><object data="' . $pdfUrl . '" type="application/pdf" width="100%" height="600px" style="border:none;">Votre navigateur ne supporte pas les PDFs. <a href="' . $pdfUrl . '">Télécharger le PDF</a>.</object></div>';
        },
        $html
    );

    // 2. FORCER Supprimer les balises </iframe> orphelines faites par TinyMCE
    $html = preg_replace('/<\/iframe>/i', '', $html);

        // 3. FORCER Supprimer sandbox="" dans les iframes faites par TinyMCE
        $html = preg_replace('/sandbox="[^"]*"/i', '', $html);

    // 4. Supprimer les div.pdf-embed imbriquées 
    while (strpos($html, '<div class="pdf-embed"><div class="pdf-embed">') !== false) {
        $html = str_replace('<div class="pdf-embed"><div class="pdf-embed">', '<div class="pdf-embed">', $html);
    }

    // 5. Supprimer les div.pdf-embed vides
    $html = preg_replace('/<div class="pdf-embed"><\/div>/i', '', $html);

    return $html;
}













    /*
    private function cleanPdfHtml(string $html): string
    {
        // 1. Supprimer sandbox="" dans les iframes
        $html = preg_replace('/sandbox="[^"]*"/i', '', $html);

        // 2. Remplace les iframes PDF par des <object>
        $html = preg_replace_callback(
            '/<iframe\s+[^>]*src="([^"]*\/uploads\/files\/[^"]*\.(pdf))"[^>]*>/i',
            function ($matches) {
                $pdfUrl = $matches[1];
                return '<object data="' . $pdfUrl . '" type="application/pdf" width="100%" height="600px" style="border:none;">Votre navigateur ne supporte pas les PDFs. <a href="' . $pdfUrl . '">Télécharger le PDF</a>.</object>';
            },
            $html
        );

        // 3. Supprimer les div.pdf-embed imbriquées (garde une seule couche)
        $html = preg_replace('/<div class="pdf-embed">\s*<div class="pdf-embed">/i', '<div class="pdf-embed">', $html);
        $html = preg_replace('/<\/div>\s*<\/div>/i', '</div>', $html);

        // 4. Supprimer les div.pdf-embed vides
        $html = preg_replace('/<div class="pdf-embed"><\/div>/i', '', $html);

        return $html;
    }
        */
}
