<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use PHPUnit\Framework\TestCase;

final class DBALMediaObjectRelationsRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private const IMAGE_ID = 'c263ce95-44b4-41b0-916f-ce72063b929b';
    private const OTHER_IMAGE_ID = 'e717473d-609d-4bf1-a008-c935c8836335';
    private const EVENT_ID = '1d4f5a1c-6f1a-4a7c-9a3e-2d1b0c3e8f01';
    private const ORGANIZER_ID = '3f6b7c3e-8b3c-4c9e-9c5a-4f3d2e5a0b03';

    private DBALMediaObjectRelationsRepository $repository;

    protected function setUp(): void
    {
        $this->setUpDatabase();

        $this->repository = new DBALMediaObjectRelationsRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_adds_and_gets_relations(): void
    {
        $this->repository->add(self::IMAGE_ID, ItemType::organizer(), self::ORGANIZER_ID);
        $this->repository->add(self::IMAGE_ID, ItemType::event(), self::EVENT_ID);
        $this->repository->add(self::OTHER_IMAGE_ID, ItemType::event(), self::EVENT_ID);

        $this->assertEquals(
            [
                new MediaObjectRelation(ItemType::event(), self::EVENT_ID),
                new MediaObjectRelation(ItemType::organizer(), self::ORGANIZER_ID),
            ],
            $this->repository->getRelations(self::IMAGE_ID)
        );
        $this->assertEquals(
            [self::IMAGE_ID, self::OTHER_IMAGE_ID],
            $this->repository->getMediaObjectIds(self::EVENT_ID)
        );
    }

    /**
     * @test
     */
    public function it_ignores_adding_an_existing_relation(): void
    {
        $this->repository->add(self::IMAGE_ID, ItemType::event(), self::EVENT_ID);
        $this->repository->add(self::IMAGE_ID, ItemType::event(), self::EVENT_ID);

        $this->assertEquals(
            [new MediaObjectRelation(ItemType::event(), self::EVENT_ID)],
            $this->repository->getRelations(self::IMAGE_ID)
        );
    }

    /**
     * @test
     */
    public function it_removes_a_relation(): void
    {
        $this->repository->add(self::IMAGE_ID, ItemType::event(), self::EVENT_ID);
        $this->repository->add(self::IMAGE_ID, ItemType::organizer(), self::ORGANIZER_ID);

        $this->repository->remove(self::IMAGE_ID, self::EVENT_ID);

        $this->assertEquals(
            [new MediaObjectRelation(ItemType::organizer(), self::ORGANIZER_ID)],
            $this->repository->getRelations(self::IMAGE_ID)
        );
    }

    /**
     * @test
     */
    public function it_removes_all_relations_of_an_item(): void
    {
        $this->repository->add(self::IMAGE_ID, ItemType::event(), self::EVENT_ID);
        $this->repository->add(self::OTHER_IMAGE_ID, ItemType::event(), self::EVENT_ID);
        $this->repository->add(self::IMAGE_ID, ItemType::organizer(), self::ORGANIZER_ID);

        $this->repository->removeAllForItem(self::EVENT_ID);

        $this->assertEquals([], $this->repository->getMediaObjectIds(self::EVENT_ID));
        $this->assertEquals(
            [new MediaObjectRelation(ItemType::organizer(), self::ORGANIZER_ID)],
            $this->repository->getRelations(self::IMAGE_ID)
        );
    }
}
