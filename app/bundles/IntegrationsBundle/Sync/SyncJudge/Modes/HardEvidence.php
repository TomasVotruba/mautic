<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncJudge\Modes;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InformationChangeRequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ConflictUnresolvedException;
use Mautic\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;

class HardEvidence implements JudgementModeInterface
{
    use DateComparisonTrait;

    /**
     * @throws ConflictUnresolvedException
     */
    public static function adjudicate(
        InformationChangeRequestDAO $leftChangeRequest,
        InformationChangeRequestDAO $rightChangeRequest,
    ): InformationChangeRequestDAO {
        if ($leftChangeRequest->getCertainChangeDateTime() === null || $rightChangeRequest->getCertainChangeDateTime() === null) {
            throw new ConflictUnresolvedException();
        }

        $certainChangeCompare = self::compareDateTimes(
            $leftChangeRequest->getCertainChangeDateTime(),
            $rightChangeRequest->getCertainChangeDateTime()
        );

        if ($certainChangeCompare === SyncJudgeInterface::NO_WINNER) {
            throw new ConflictUnresolvedException();
        }

        if ($certainChangeCompare === SyncJudgeInterface::LEFT_WINNER) {
            return $leftChangeRequest;
        }

        return $rightChangeRequest;
    }
}
