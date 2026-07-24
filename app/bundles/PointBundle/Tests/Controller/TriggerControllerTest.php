<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PointBundle\Model\TriggerModel;
use Mautic\PointBundle\Tests\Functional\TriggerTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class TriggerControllerTest extends MauticMysqlTestCase
{
    use TriggerTrait;

    /**
     * @psalm-param non-empty-string $name
     *
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function __construct(
        string $name,
        private readonly \Mautic\PointBundle\Entity\TriggerRepository $triggerRepository,
    ) {
        parent::__construct($name);
    }

    public function testIndexActionWithoutPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points/triggers');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testIndexActionWithPage(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/points/triggers/1');

        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testCloneAction(): void
    {
        /** @var TriggerModel $triggerModel */
        $triggerModel = self::getContainer()->get('mautic.point.model.trigger');

        $triggerRepo      = $this->triggerRepository;
        $triggerEventRepo = $triggerModel->getEventRepository();

        $trigger = $this->createTrigger('Trigger', 5);
        $this->createAddTagEvent('tag1', $trigger);
        $this->createAddTagEvent('tag2', $trigger);

        $this->em->flush();
        $this->em->clear();

        $this->assertCount(1, $triggerRepo->findAll());
        $this->assertCount(2, $triggerEventRepo->findAll());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/points/triggers/clone/'.$trigger->getId());
        $this->assertResponseIsSuccessful();

        $form    = $crawler->selectButton('Save')->form();
        $this->client->submit($form);

        $this->assertCount(2, $triggerRepo->findAll());
        $this->assertCount(4, $triggerEventRepo->findAll());
    }
}
