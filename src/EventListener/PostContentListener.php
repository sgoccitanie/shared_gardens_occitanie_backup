<?php

/**
 * Écoute les événements pour créer/modifier un article
 * Objectif : Nettoyer le contenu pour les PDFs insérés => CONTRER les erreurs générées par TinyMCE
 *  -> Supprimer les attributs sandbox="",
 *  -> Remplacer les <iframe> PDF par des <object> pour assurer la compatibilité,
 *  ->> Supprimer les imbrications et div.pdf-embed vides générées automatiquement.
 */

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

    private function cleanPdfHtml(string $html): string
    {
        // Remplacer les <iframe> par des <object>
        $html = preg_replace_callback(
            '/<iframe[^>]+src="([^"]+\.pdf)"[^>]*><\/iframe>/i',
            function ($matches) {
                $pdfUrl = $matches[1];
                return '<div class="pdf-embed"><object data="' . $pdfUrl . '" type="application/pdf" width="100%" height="600px" style="border:none;">Votre navigateur ne supporte pas les PDFs. <a href="' . $pdfUrl . '">Télécharger le PDF</a>.</object></div>';
            },
            $html
        );

        // Supprimer sandbox="" dans les iframes faites par TinyMCE
        $html = preg_replace('/sandbox="[^"]*"/i', '', $html);

        // Supprimer les div.pdf-embed imbriquées
        while (strpos($html, '<div class="pdf-embed"><div class="pdf-embed">') !== false) {
            $html = str_replace('<div class="pdf-embed"><div class="pdf-embed">', '<div class="pdf-embed">', $html);
        }

        // Supprimer les div.pdf-embed vides
        $html = preg_replace('/<div class="pdf-embed">\s*<\/div>/i', '', $html);

        return $html;
    }
}
