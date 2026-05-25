<?php

/* Écouter les événements pour créer/modifier un pdf dans un article =  contrer les erreurs  générées par TinyMCE */

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

    //* Nettoyer le contenu HTML d'un article avant sauvegarde *//
    public function cleanPostContent($event): void
    {
        $entity = $event->getEntityInstance();

        // Cibler uniquement l'entité Posts
        if (!$entity instanceof Posts) {
            return;
        }

        // Décoder le titre pour éviter les entités HTML (ex: &#039; pour l'apostrophe)
        $title = $entity->getTitle();
        if (!empty($title)) {
            $entity->setTitle(html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        $content = $entity->getContent();
        if (empty($content)) {
            return;
        }

        // Nettoyer le HTML => supprimer sandbox="" et les imbrications vides générées par TinyMCE
        $cleanedContent = $this->cleanPdfHtml($content);
        $entity->setContent($cleanedContent);
    }

    //* Nettoyer le HTML pour les PDFs insérés via TinyMCE *//
    private function cleanPdfHtml(string $html): string
    {
        // Remplacer les <iframe> par des <object>
        $html = preg_replace_callback(
            '/<iframe[^>]+src="([^"]+\.pdf)"[^>]*><\/iframe>/i',
            function ($matches) {
                $pdfUrl = $matches[1];
                return '<div class="pdf-embed"><object data="' . $pdfUrl . '" type="application/pdf" width="100%" height="1122px" style="border:none;">Votre navigateur ne supporte pas les PDFs. <a href="' . $pdfUrl . '">Télécharger le PDF</a>.</object></div>';
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
