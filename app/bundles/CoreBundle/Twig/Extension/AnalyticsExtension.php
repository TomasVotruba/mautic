<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\AnalyticsHelper;

final class AnalyticsExtension
{
    public function __construct(
        private readonly AnalyticsHelper $helper,
    ) {
    }

    #[\Twig\Attribute\AsTwigFunction(name: 'analyticsGetCode', isSafe: ['all'])]
    public function getCode(): string
    {
        return $this->helper->getCode();
    }
}
