<?php

namespace App\EventSubscriber;

use App\Entity\Addresses;
use App\Entity\Cities;
use App\Entity\Countries;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::prePersist)]
class CreateCityFromAddressSubscriber
{
    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Addresses) {
            return;
        }

        if ($entity->getCity() !== null) {
            return;
        }

        if (!$entity->getCityName()) {
            return;
        }

        $em = $args->getObjectManager();

        $city = new Cities();
        $city->setName($entity->getCityName());
        $city->setPostalcode($entity->getCityPostalcode());
        $city->setAreaName($entity->getCityAreaName() ?? '');
        $city->setDptName($entity->getCityDptName() ?? '');
        $city->setCountry($em->getReference(Countries::class, 1));

        $em->persist($city);
        $entity->setCity($city);
    }
}
