<?php

namespace App\EventSubscriber;

use App\Entity\Addresses;
use App\Service\GeocodingService;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class GeocodeAddressSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly GeocodingService $geocodingService,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['geocodeAddress'],
            BeforeEntityUpdatedEvent::class => ['geocodeAddressOnUpdate'],
        ];
    }

    public function geocodeAddress(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if ($entity instanceof Addresses) {
            $this->doGeocode($entity);
        }
    }

    public function geocodeAddressOnUpdate(BeforeEntityUpdatedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if ($entity instanceof Addresses) {
            $this->doGeocode($entity);
        }
    }

    private function doGeocode(Addresses $address): void
    {

        // Si la ville existe déjà, on utilise ses infos
        if ($address->getCity() !== null) {
            $postalCode = $address->getCity()->getPostalcode();
            $cityName = $address->getCity()->getName();
        } else {
            // Sinon, on utilise les champs virtuels (nouvelle ville en cours de création)
            $postalCode = $address->getCityPostalcode() ?? '';
            $cityName = $address->getCityName() ?? '';
        }
        // Construction de l'adresse complète
        $fullAddress = trim(sprintf(
            '%s %s %s',
            $address->getStreet(),
            $postalCode,
            $cityName
        ));

        if (empty(trim($fullAddress))) {
            return;
        }

        $coordinates = $this->geocodingService->geocode($fullAddress);

        if ($coordinates !== null) {
            $address->setLatitude($coordinates['latitude']);
            $address->setLongitude($coordinates['longitude']);
        }
    }
}
