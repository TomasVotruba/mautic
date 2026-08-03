<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Twig\Extension;

use Mautic\CoreBundle\Twig\Helper\MautibotHelper;

final class MautibotExtension
{
    public function __construct(
        private readonly MautibotHelper $mautibotHelper,
    ) {
    }

    /**
     * @param string $image One of openMouth | smile | wave
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'mautibotGetImage', isSafe: ['all'])]
    public function getImage(string $image): string
    {
        return $this->mautibotHelper->getImage($image);
    }
}
