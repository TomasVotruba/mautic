<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Common\DataFixtures\Event;

use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PreExecuteEvent extends Event
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private int $purgeMode,
    ) {
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function isDelete(): bool
    {
        return $this->purgeMode === ORMPurger::PURGE_MODE_DELETE;
    }

    public function isTruncate(): bool
    {
        return $this->purgeMode === ORMPurger::PURGE_MODE_TRUNCATE;
    }
}
