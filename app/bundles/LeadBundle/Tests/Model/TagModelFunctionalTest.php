<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;
use Mautic\LeadBundle\Model\TagModel;

final class TagModelFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @psalm-param non-empty-string $name
     *
     * @internal This method is not covered by the backward compatibility promise for PHPUnit
     */
    public function __construct(
        string $name,
        private readonly TagRepository $tagRepository,
    ) {
        parent::__construct($name);
    }

    public function testDeleteOrphanTags(): void
    {
        /** @var TagModel $model */
        $model = self::getContainer()->get('mautic.lead.model.tag');

        $tags = [
            'tag1',
            'tag2',
            'tag3',
            'tag4',
        ];

        foreach ($tags as $tagName) {
            $tag = new Tag();
            $tag->setTag($tagName);
            $model->saveEntity($tag);
        }

        /** @var TagRepository $tagRepository */
        $tagRepository = $this->tagRepository;
        $count         = $tagRepository->count([]);
        $this->assertSame(4, $count);

        $tagRepository->deleteOrphans();
        $count = $tagRepository->count([]);
        $this->assertSame(0, $count);
    }
}
