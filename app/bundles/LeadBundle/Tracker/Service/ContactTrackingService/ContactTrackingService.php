<?php

namespace Mautic\LeadBundle\Tracker\Service\ContactTrackingService;

use Mautic\CoreBundle\Helper\CookieHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadDeviceRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\MergeRecordRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Used to ensure that contacts tracked under the old method are continued to be tracked under the new.
 */
final class ContactTrackingService implements ContactTrackingServiceInterface
{
    public function __construct(
        private CookieHelper $cookieHelper,
        private LeadDeviceRepository $leadDeviceRepository,
        private LeadRepository $leadRepository,
        private MergeRecordRepository $mergeRecordRepository,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * @return Lead|null
     */
    public function getTrackedLead()
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request === null) {
            return null;
        }

        $trackingId = $this->getTrackedIdentifier();
        if ($trackingId === null) {
            return null;
        }

        $leadId = $this->cookieHelper->getCookie($trackingId);
        if ($leadId === null) {
            $leadId = $request->get('mtc_id');
            if ($leadId === null) {
                return null;
            }
        }

        $lead = $this->leadRepository->getEntity($leadId);
        if ($lead === null) {
            // Check if this contact was merged into another and if so, return the new contact
            $lead = $this->mergeRecordRepository->findMergedContact($leadId);

            if ($lead === null) {
                return null;
            }

            // Hydrate fields with custom field data
            $fields = $this->leadRepository->getFieldValues($lead->getId());
            $lead->setFields($fields);
        }

        $anotherDeviceAlreadyTracked = $this->leadDeviceRepository->isAnyLeadDeviceTracked($lead);

        return $anotherDeviceAlreadyTracked ? null : $lead;
    }

    /**
     * @return string|null
     */
    public function getTrackedIdentifier()
    {
        return $this->cookieHelper->getCookie('mautic_session_id');
    }
}
