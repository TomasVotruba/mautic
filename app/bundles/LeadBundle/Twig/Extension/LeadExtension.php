<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Twig\Extension;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Twig\Helper\AvatarHelper;

final class LeadExtension
{
    public function __construct(
        private readonly AvatarHelper $avatarHelper,
    ) {
    }

    /**
     * @see AvatarHelper::getAvatar
     *
     * @return mixed
     */
    #[\Twig\Attribute\AsTwigFunction(name: 'leadGetAvatar')]
    public function getAvatar(Lead $lead)
    {
        return $this->avatarHelper->getAvatar($lead);
    }
}
