<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use PHPUnit\Framework\TestCase;

final class EventRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    public function testDecreaseFailedCount(): void
    {
        $emMock           = $this->createMock(EntityManager::class);
        $connMock         = $this->createMock(Connection::class);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $expressionMock   = $this->createMock(Expr::class);

        $queryBuilderMock->expects($this->atLeastOnce())
            ->method('expr')
            ->willReturn($expressionMock);

        $expressionMock->expects($this->once())
            ->method('eq')
            ->with('id', ':id')
            ->willReturn('id = :id');

        $queryBuilderMock->expects($this->atLeastOnce())
            ->method('expr')
            ->willReturn($expressionMock);

        $expressionMock->expects($this->once())
            ->method('gt')
            ->with('failed_count', 0)
            ->willReturn('failed_count > 0');

        $queryBuilderMock->expects($this->once())
            ->method('update')
            ->with(MAUTIC_TABLE_PREFIX.'campaign_events')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('set')
            ->with('failed_count', 'failed_count - 1')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('where')
            ->with('id = :id')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('andWhere')
            ->with('failed_count > 0')
            ->willReturn($queryBuilderMock);

        $queryBuilderMock->expects($this->once())
            ->method('setParameter')
            ->with('id', 42)
            ->willReturn($queryBuilderMock);

        $connMock
            ->method('createQueryBuilder')
            ->willReturn($queryBuilderMock);

        $emMock->expects($this->once())
            ->method('getConnection')
            ->willReturn($connMock);

        $eventRepository = $this->configureRepository(Event::class, $emMock);
        $this->connection->method('createQueryBuilder')
            ->willReturnCallback(fn (): \PHPUnit\Framework\MockObject\MockObject => $queryBuilderMock);

        $eventMock       = $this->createMock(Event::class);
        $eventMock->expects($this->once())->method('getId')
            ->willReturn(42);

        $eventRepository->decreaseFailedCount($eventMock);
    }
}
