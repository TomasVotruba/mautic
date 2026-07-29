<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ConfigServiceToAutowiredServiceRector\Source;

use Symfony\Contracts\Translation\TranslatorInterface;

final class AttributeAwareHelper
{
    /**
     * @param array<string, string> $attributes
     */
    public function __construct(
        private TranslatorInterface $translator,
        private array $attributes,
    ) {
    }
}
