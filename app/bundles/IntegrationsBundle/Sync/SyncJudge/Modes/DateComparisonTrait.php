<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncJudge\Modes;

use Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

trait DateComparisonTrait
{
    /**
     * @return string self::LEFT_WINNER|self::RIGHT_WINNER|self::NO_WINNER
     */
    private static function compareDateTimes(?\DateTimeInterface $leftDateTime = null, ?\DateTimeInterface $rightDateTime = null): string
    {
        if ($leftDateTime !== null && ($rightDateTime === null || $leftDateTime > $rightDateTime)) {
            return SyncJudgeInterface::LEFT_WINNER;
        }

        if ($rightDateTime !== null && ($leftDateTime === null || $rightDateTime > $leftDateTime)) {
            return SyncJudgeInterface::RIGHT_WINNER;
        }

        return SyncJudgeInterface::NO_WINNER;
    }
}
