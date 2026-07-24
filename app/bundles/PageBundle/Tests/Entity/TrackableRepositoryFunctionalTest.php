<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\PageBundle\Entity\Hit;
use Mautic\PageBundle\Entity\Redirect;
use Mautic\PageBundle\Entity\Trackable;

final class TrackableRepositoryFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @psalm-param non-empty-string $name
     *
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function __construct(
        string $name,
        private readonly \Mautic\PageBundle\Entity\TrackableRepository $trackableRepository,
    ) {
        parent::__construct($name);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testGetCount(): void
    {
        $redirectOne = $this->createRedirect('http://example.com/a/b');
        $redirectTwo = $this->createRedirect('http://example.com/a/b/c');

        $leadA = $this->createLead('john.a@doe.com');
        $leadB = $this->createLead('john.b@doe.com');
        $leadC = $this->createLead('john.c@doe.com');
        $leadD = $this->createLead('john.d@doe.com');

        $this->createTrackable('channel-a', 1, $redirectOne);
        $this->createHit($leadA, $redirectOne, 'channel-a', 1);
        $this->createHit($leadB, $redirectOne, 'channel-a', 1);
        $this->createTrackable('channel-a', 2, $redirectTwo);
        $this->createHit($leadC, $redirectOne, 'channel-a', 2);

        $this->createTrackable('channel-b', 2, $redirectOne);
        $this->createHit($leadA, $redirectOne, 'channel-b', 2);
        $this->createHit($leadC, $redirectOne, 'channel-b', 2);

        $this->createTrackable('channel-b', 3, $redirectTwo);
        $this->createHit($leadB, $redirectOne, 'channel-b', 3);
        $this->createHit($leadD, $redirectOne, 'channel-b', 3);

        $segment = $this->createSegment();
        $this->addContactsToSegment($segment, [$leadA, $leadB, $leadC]);

        $this->assertSame('2', $this->trackableRepository->getCount('channel-a', [1, 2], null));

        $this->assertEmpty($this->trackableRepository->getCount('channel-a', [2], [$segment->getId()]));

        $count = $this->trackableRepository->getCount('channel-b', [1, 2, 3], [$segment->getId()]);

        $this->assertNotEmpty($count);
        $this->assertArrayHasKey($segment->getId(), $count);
        $this->assertSame(2, (int) $count[$segment->getId()]);
    }

    private function createTrackable(string $channel, int $channelId, Redirect $redirect): void
    {
        $trackable = new Trackable();
        $trackable->setChannel($channel)
            ->setChannelId($channelId)
            ->setRedirect($redirect);

        $this->trackableRepository->saveEntity($trackable);
    }

    private function createRedirect(string $url): Redirect
    {
        $redirect = new Redirect();
        $redirect->setUrl($url);
        $redirect->setRedirectId();

        $this->trackableRepository->saveEntity($redirect);

        return $redirect;
    }

    private function createHit(Lead $lead, Redirect $redirect, string $source, int $sourceId): void
    {
        $hit = new Hit();
        $hit->setLead($lead);
        $hit->setDateHit(new \DateTime());
        $hit->setTrackingId('random');
        $hit->setCode(200);
        $hit->setSource($source);
        $hit->setSourceId($sourceId);
        $hit->setRedirect($redirect);

        $this->trackableRepository->saveEntity($hit);
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('test');
        $segment->setPublicName('test');
        $segment->setAlias('test-alias');

        $this->trackableRepository->saveEntity($segment);

        return $segment;
    }

    private function createLead(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);

        $this->trackableRepository->saveEntity($lead);

        return $lead;
    }

    /**
     * @param Lead[] $leads
     */
    private function addContactsToSegment(LeadList $segment, array $leads): void
    {
        $contacts = [];
        foreach ($leads as $lead) {
            $listLead = new ListLead();
            $listLead->setLead($lead);
            $listLead->setList($segment);
            $listLead->setDateAdded(new \DateTime());
            $contacts[] = $listLead;
        }
        $this->trackableRepository->saveEntities($contacts);
    }
}
