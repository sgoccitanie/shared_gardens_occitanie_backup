<?php
// src/EventListener/AdminListener.php

namespace App\EventListener;

use App\Service\Utils;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;

class AdminListener implements EventSubscriberInterface
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
            AfterEntityPersistedEvent::class => 'onPostPersist',
            AfterEntityUpdatedEvent::class => 'onPostUpdate',
        ];
    }

    public function onPrePersist($event)
    {
        $entity = $event->getEntityInstance();
        $this->cleanInputs($entity);
    }

    public function onPreUpdate($event)
    {
        $entity = $event->getEntityInstance();
        $this->cleanInputs($entity);
    }

    public function onPostPersist($event)
    {
        $entity = $event->getEntityInstance();
        $this->decodeEntityFields($entity);
    }

    public function onPostUpdate($event)
    {
        $entity = $event->getEntityInstance();
        $this->decodeEntityFields($entity);
    }

    private function cleanInputs($entity)
    {
        if (property_exists($entity, 'title') && method_exists($entity, 'getTitle') && method_exists($entity, 'setTitle')) {
            $entity->setTitle(Utils::cleanInputStatic($entity->getTitle()));
        }
    }

    private function decodeEntityFields($entity)
    {
        // Décoder uniquement les champs de type string
        $stringFields = ['logo', 'banner', 'description', 'mantra', 'name', 'acronyme', 'status', 'email', 'mobile'];

        foreach ($stringFields as $field) {
            if (property_exists($entity, $field)) {
                $getter = 'get' . ucfirst($field);
                $setter = 'set' . ucfirst($field);

                if (method_exists($entity, $getter) && method_exists($entity, $setter)) {
                    $value = $entity->$getter();
                    if (is_string($value)) {
                        $entity->$setter(Utils::decode($value));
                    }
                }
            }
        }
    }
}
