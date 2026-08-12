<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\Result;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Result\Responses;

final class ResponsesTest extends \PHPUnit\Framework\TestCase
{
    public function testExtractingResponsesFromLog(): void
    {
        $actionEvent = $this->createMock(Event::class);
        $actionEvent->expects($this->once())->method('getEventType')
            ->willReturn(Event::TYPE_ACTION);
        $actionEvent->expects($this->atLeastOnce())->method('getType')
            ->willReturn('actionEvent');
        $actionEvent->expects($this->once())->method('getId')
            ->willReturn(1);

        // BC should set response as just test
        $actionLog = $this->createMock(LeadEventLog::class);
        $actionLog->expects($this->once())->method('getEvent')
            ->willReturn($actionEvent);
        $actionLog->expects($this->once())->method('getMetadata')
            ->willReturn(['timeline' => 'test']);

        $action2Event = $this->createMock(Event::class);
        $action2Event->expects($this->once())->method('getEventType')
            ->willReturn(Event::TYPE_ACTION);
        $action2Event->expects($this->atLeastOnce())->method('getType')
            ->willReturn('action2Event');
        $action2Event->expects($this->once())->method('getId')
            ->willReturn(2);

        // Response should be full array
        $action2Log = $this->createMock(LeadEventLog::class);
        $action2Log->expects($this->once())->method('getEvent')
            ->willReturn($action2Event);
        $action2Log->expects($this->once())->method('getMetadata')
            ->willReturn(['timeline' => 'test', 'something' => 'else']);

        // Response should be full array
        $conditionEvent = $this->createMock(Event::class);
        $conditionEvent->expects($this->once())->method('getEventType')
            ->willReturn(Event::TYPE_CONDITION);
        $conditionEvent->expects($this->atLeastOnce())->method('getType')
            ->willReturn('conditionEvent');
        $conditionEvent->expects($this->once())->method('getId')
            ->willReturn(3);

        $conditionLog = $this->createMock(LeadEventLog::class);
        $conditionLog->expects($this->once())->method('getEvent')
            ->willReturn($conditionEvent);
        $conditionLog->expects($this->once())->method('getMetadata')
            ->willReturn(['something' => 'else']);

        $logs = new ArrayCollection([$actionLog, $action2Log, $conditionLog]);

        $responses = new Responses();
        $responses->setFromLogs($logs);

        $actions = [
            'actionEvent'  => [
                1 => 'test',
            ],
            'action2Event' => [
                2 => [
                    'timeline'  => 'test',
                    'something' => 'else',
                ],
            ],
        ];

        $conditions = [
            'conditionEvent' => [
                3 => [
                    'something' => 'else',
                ],
            ],
        ];

        $this->assertEquals($actions, $responses->getActionResponses());
        $this->assertEquals($conditions, $responses->getConditionResponses());
    }
}
