<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ModelGetRepositoryToRepositoryServiceRector\Source;

final class SomeRepository
{
    public function findThing(int $id): ?object
    {
        return null;
    }
}
