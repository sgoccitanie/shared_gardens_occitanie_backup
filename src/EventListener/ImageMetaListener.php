<?php

namespace App\EventListener;

use App\Entity\Posts;
use App\Entity\Postmeta;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ImageMetaListener implements EventSubscriberInterface
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => 'onPrePersist',
            BeforeEntityUpdatedEvent::class => 'onPreUpdate',
        ];
    }

    public function onPrePersist(BeforeEntityPersistedEvent $event): void
    {
        $this->extractImageMetas($event);
    }

    public function onPreUpdate(BeforeEntityUpdatedEvent $event): void
    {
        $this->extractImageMetas($event);
    }

    private function extractImageMetas($event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof Posts) {
            return;
        }

        $content = $entity->getContent();
        if (empty($content)) {
            return;
        }

        // Supprimer les anciennes métadonnées d'images
        foreach ($entity->getMetas() as $meta) {
            if (strpos($meta->getMetaKey(), 'image_size_') === 0) {
                $entity->removeMeta($meta);
                $this->entityManager->remove($meta);
            }
        }

        // Extraire les images et leurs tailles
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $images = $dom->getElementsByTagName('img');

        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $width = $img->getAttribute('width');
            $height = $img->getAttribute('height');

            if (!empty($src) && (!empty($width) || !empty($height))) {
                $meta = new Postmeta();
                $meta->setMetaKey('image_size_' . md5($src));
                $meta->setMetaValue(json_encode(['width' => $width, 'height' => $height]));
                $entity->addMeta($meta);
                $meta->setPost($entity);
                $this->entityManager->persist($meta);
            }
        }
    }
}
