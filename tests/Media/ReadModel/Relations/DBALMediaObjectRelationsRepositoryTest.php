<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use PHPUnit\Framework\TestCase;

final class DBALMediaObjectRelationsRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private const MEDIA_OBJECT_ID = 'de305d54-75b4-431b-adb2-eb6b9e546014';
    private const OTHER_MEDIA_OBJECT_ID = '0b2b8f3a-6d7e-4a43-8a9c-2f6e1c3d4b5a';
    private const EVENT_ID = '1a9ec5fe-1e42-4c38-9b6f-60e7a2b3a1c1';
    private const PLACE_ID = '2f1c4e6a-8d4b-4f0e-9d2a-7c3b5e1f0a92';
    private const ORGANIZER_ID = '3c7d2e1f-5a6b-4c8d-9e0f-1a2b3c4d5e6f';

    private DBALMediaObjectRelationsRepository $repository;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->repository = new DBALMediaObjectRelationsRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_finds_the_items_using_a_media_object(): void
    {
        $event = new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID);
        $place = new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::place(), self::PLACE_ID);
        $organizer = new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::organizer(), self::ORGANIZER_ID);
        $this->repository->add($event);
        $this->repository->add($place);
        $this->repository->add($organizer);
        $this->repository->add(
            new MediaObjectRelation(new Uuid(self::OTHER_MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID)
        );

        $this->assertEquals(
            [$event, $place, $organizer],
            $this->repository->getByMediaObject(new Uuid(self::MEDIA_OBJECT_ID))
        );
    }

    /**
     * @test
     */
    public function it_finds_the_media_objects_of_an_item(): void
    {
        $first = new MediaObjectRelation(new Uuid(self::OTHER_MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID);
        $second = new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID);
        $this->repository->add($first);
        $this->repository->add($second);
        $this->repository->add(
            new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::place(), self::PLACE_ID)
        );

        $this->assertEquals([$first, $second], $this->repository->getByItem(self::EVENT_ID));
    }

    /**
     * @test
     */
    public function it_ignores_adding_a_relation_twice(): void
    {
        $relation = new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID);
        $this->repository->add($relation);
        $this->repository->add($relation);

        $this->assertEquals([$relation], $this->repository->getByMediaObject(new Uuid(self::MEDIA_OBJECT_ID)));
    }

    /**
     * @test
     */
    public function it_removes_one_relation(): void
    {
        $this->repository->add(
            new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID)
        );
        $kept = new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::place(), self::PLACE_ID);
        $this->repository->add($kept);

        $this->repository->remove(new Uuid(self::MEDIA_OBJECT_ID), self::EVENT_ID);

        $this->assertEquals([$kept], $this->repository->getByMediaObject(new Uuid(self::MEDIA_OBJECT_ID)));
    }

    /**
     * @test
     */
    public function it_removes_all_relations_of_an_item(): void
    {
        $this->repository->add(
            new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID)
        );
        $this->repository->add(
            new MediaObjectRelation(new Uuid(self::OTHER_MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID)
        );
        $kept = new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), ItemType::place(), self::PLACE_ID);
        $this->repository->add($kept);

        $this->repository->removeItem(self::EVENT_ID);

        $this->assertEquals([], $this->repository->getByItem(self::EVENT_ID));
        $this->assertEquals([$kept], $this->repository->getByMediaObject(new Uuid(self::MEDIA_OBJECT_ID)));
    }
}
