<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Stat;

use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Model\EmailStatModel;
use Mautic\EmailBundle\Stat\Exception\StatNotFoundException;
use Mautic\EmailBundle\Stat\StatHelper;
use Mautic\LeadBundle\Entity\Lead;

final class StatHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testStatsAreCreatedAndDeleted(): void
    {
        $emailStatmodel     = $this->createMock(EmailStatModel::class);
        $mockStatRepository = $this->createMock(StatRepository::class);

        $emailStatmodel->expects($this->atLeastOnce())->method('getRepository')->willReturn($mockStatRepository);

        $mockStatRepository->expects($this->once())
            ->method('deleteStats')
            ->with([1, 2, 3, 4, 5]);

        $statHelper = new StatHelper($emailStatmodel);

        $mockEmail = $this->createMock(Email::class);
        $mockEmail->expects($this->atLeastOnce())->method('getId')
            ->willReturn(15);

        $counter = 1;
        while ($counter <= 5) {
            $stat = $this->createMock(Stat::class);

            $stat->expects($this->atLeastOnce())->method('getId')
                ->willReturn((string) $counter);

            $stat->expects($this->once())->method('getEmail')
                ->willReturn($mockEmail);

            $lead = $this->createMock(Lead::class);

            $lead->expects($this->atLeastOnce())->method('getId')
                ->willReturn($counter * 10);

            $stat->expects($this->once())->method('getLead')
                ->willReturn($lead);

            $emailAddress = "contact{$counter}@test.com";
            $statHelper->storeStat($stat, $emailAddress);

            // Delete it
            try {
                $reference = $statHelper->getStat($emailAddress);
                $this->assertEquals($reference->getLeadId(), $counter * 10);
                $statHelper->markForDeletion($reference);
            } catch (StatNotFoundException) {
                $this->fail("Stat not found for {$emailAddress}");
            }

            ++$counter;
        }

        $statHelper->deletePending();
    }

    public function testExceptionIsThrownIfEmailAddressIsNotFound(): void
    {
        $this->expectException(StatNotFoundException::class);

        $statHelper = new StatHelper($this->createStub(EmailStatModel::class));

        $statHelper->getStat('nada@nada.com');
    }
}
