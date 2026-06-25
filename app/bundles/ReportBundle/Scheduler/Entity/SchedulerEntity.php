<?php

namespace Mautic\ReportBundle\Scheduler\Entity;

use Mautic\ReportBundle\Scheduler\Enum\SchedulerEnum;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;

class SchedulerEntity implements SchedulerInterface
{
    /**
     * @param bool        $isScheduled
     * @param string|null $scheduleUnit
     * @param string|null $scheduleDay
     * @param string|null $scheduleMonthFrequency
     */
    public function __construct(
        private $isScheduled,
        private $scheduleUnit,
        private $scheduleDay,
        private $scheduleMonthFrequency,
    ) {
    }

    /**
     * @return bool
     */
    public function isScheduled()
    {
        return $this->isScheduled;
    }

    /**
     * @return string|null
     */
    public function getScheduleUnit()
    {
        return $this->scheduleUnit;
    }

    /**
     * @return string|null
     */
    public function getScheduleDay()
    {
        return $this->scheduleDay;
    }

    /**
     * @return string|null
     */
    public function getScheduleMonthFrequency()
    {
        return $this->scheduleMonthFrequency;
    }

    public function isScheduledNow(): bool
    {
        return $this->getScheduleUnit() === SchedulerEnum::UNIT_NOW;
    }

    public function isScheduledDaily(): bool
    {
        return $this->getScheduleUnit() === SchedulerEnum::UNIT_DAILY;
    }

    public function isScheduledWeekly(): bool
    {
        return $this->getScheduleUnit() === SchedulerEnum::UNIT_WEEKLY;
    }

    public function isScheduledMonthly(): bool
    {
        return $this->getScheduleUnit() === SchedulerEnum::UNIT_MONTHLY;
    }

    public function isScheduledWeekDays(): bool
    {
        return $this->getScheduleDay() === SchedulerEnum::DAY_WEEK_DAYS;
    }
}
