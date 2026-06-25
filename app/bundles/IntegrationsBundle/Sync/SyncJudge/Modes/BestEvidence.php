<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncJudge\Modes;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

class BestEvidence implements JudgementModeInterface
{
    use DateComparisonTrait;

    /**
     * @throws ConflictUnresolvedException
     */
    public static function adjudicate(
        InformationChangeRequestDAO $leftChangeRequest,
        InformationChangeRequestDAO $rightChangeRequest,
    ): InformationChangeRequestDAO {
        try {
            return HardEvidence::adjudicate($leftChangeRequest, $rightChangeRequest);
        } catch (ConflictUnresolvedException) {
        }

        if ($leftChangeRequest->getPossibleChangeDateTime() === null || $rightChangeRequest->getPossibleChangeDateTime() === null) {
            throw new ConflictUnresolvedException();
        }

        $possibleChangeCompare = self::compareDateTimes(
            $leftChangeRequest->getPossibleChangeDateTime(),
            $rightChangeRequest->getPossibleChangeDateTime()
        );

        if ($possibleChangeCompare === SyncJudgeInterface::NO_WINNER) {
            throw new ConflictUnresolvedException();
        }

        if ($possibleChangeCompare === SyncJudgeInterface::LEFT_WINNER) {
            return $leftChangeRequest;
        }

        return $rightChangeRequest;
    }
}
