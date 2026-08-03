<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

final class ExceptionExtension
{
    #[\Twig\Attribute\AsTwigFunction(name: 'getRootPath', isSafe: ['all'])]
    public function getRoot(): string
    {
        return realpath(__DIR__.'/../../../../../../');
    }
}
