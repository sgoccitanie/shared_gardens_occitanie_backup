<?php

namespace App\EventListener;

use App\Service\HeaderService;
use App\Repository\AssociationRepository;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TwigListener implements EventSubscriberInterface
{
    public function __construct(
        private HeaderService $headerService,
        private AssociationRepository $assoRepo
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        // Récupérer l'ID de l'association active
        $firstAssociation = $this->assoRepo->findOneBy([], ['id' => 'ASC']);
        $assoId = $firstAssociation ? $firstAssociation->getId() : 1;

        // Récupérer les données de l'association
        $headerData = $this->headerService->getHeaderData($assoId);
        $logoPath = $this->headerService->getLogoPath($headerData['assoLogo']);
        $formattedMantra = $this->headerService->getFormattedMantra($headerData['assoMantra']);

        // Injecter les données dans la requête
        $request->attributes->set('headerData', $headerData);
        $request->attributes->set('logoPath', $logoPath);
        $request->attributes->set('formattedMantra', $formattedMantra);

        // Fournir une valeur par défaut pour adhesionUrl si elle n'existe pas
        if (!$request->attributes->has('adhesionUrl')) {
            $request->attributes->set('adhesionUrl', 'https://www.helloasso.com/associations/le-reseau-des-semeurs-de-jardins/adhesions/adhesion-annuelle-au-reseau-des-semeurs-de-jardins-2026');
        }
    }
}
