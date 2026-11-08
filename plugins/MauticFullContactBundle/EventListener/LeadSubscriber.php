<?php

namespace MauticPlugin\MauticFullContactBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\LeadPostSaveEvent;
use MauticPlugin\MauticFullContactBundle\Helper\LookupHelper;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class LeadSubscriber
{
    public function __construct(
        private LookupHelper $lookupHelper,
    ) {
    }

    #[AsEventListener(
        event: LeadPostSaveEvent::class,
    )]
    public function leadPostSave(LeadEvent $event): void
    {
        $this->lookupHelper->lookupContact($event->getLead(), true, true);
    }

    #[AsEventListener]
    public function companyPostSave(EntityPostSave $event): void
    {
        $company = $event->getEntity();
        if (!$company instanceof Company) {
            return;
        }

        $this->lookupHelper->lookupCompany($company, true, true);
    }
}
