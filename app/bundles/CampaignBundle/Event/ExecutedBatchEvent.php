<?php declare(strict_types=1);

namespace Mautic\CampaignBundle\Event;

use Doctrine\Common\Collections\ArrayCollection;

class ExecutedBatchEvent extends AbstractLogCollectionEvent
{
    /**
     * @return ArrayCollection
     */
    public function getExecuted()
    {
        return $this->logs;
    }
}
