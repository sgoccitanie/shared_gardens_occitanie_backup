<?php

namespace App\Twig\Extension;

use App\Service\CommonDataService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class CommonDataExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly CommonDataService $commonDataService,
    ) {}

    public function getGlobals(): array
    {
        return array_merge(
            $this->commonDataService->getFullHeaderData(),
            $this->commonDataService->getCommonData(),
        );
    }
}
