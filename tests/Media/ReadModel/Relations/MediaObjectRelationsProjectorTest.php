<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\Image\ImagesUpdatedFromUDB2 as EventImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Event\Events\ImageRemoved as EventImageRemoved;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Events\ImageAdded as OrganizerImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved as OrganizerImageRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Place\Events\ImageAdded as PlaceImageAdded;
use PHPUnit\Framework\TestCase;

final class MediaObjectRelationsProjectorTest extends TestCase
{
    use DBALTestConnectionTrait;

    private const MEDIA_OBJECT_ID = 'de305d54-75b4-431b-adb2-eb6b9e546014';
    private const OTHER_MEDIA_OBJECT_ID = '0b2b8f3a-6d7e-4a43-8a9c-2f6e1c3d4b5a';
    private const EVENT_ID = '1a9ec5fe-1e42-4c38-9b6f-60e7a2b3a1c1';
    private const COPIED_EVENT_ID = '4d8e3f2a-6b7c-4d9e-8f0a-2b3c4d5e6f7a';
    private const PLACE_ID = '2f1c4e6a-8d4b-4f0e-9d2a-7c3b5e1f0a92';
    private const ORGANIZER_ID = '3c7d2e1f-5a6b-4c8d-9e0f-1a2b3c4d5e6f';

    private DBALMediaObjectRelationsRepository $repository;
    private MediaObjectRelationsProjector $projector;

    protected function setUp(): void
    {
        $this->setUpDatabase();
        $this->repository = new DBALMediaObjectRelationsRepository($this->getConnection());
        $this->projector = new MediaObjectRelationsProjector($this->repository);
    }

    /**
     * @test
     */
    public function it_adds_a_relation_for_each_item_type_that_adds_an_image(): void
    {
        $this->project(new EventImageAdded(self::EVENT_ID, $this->image(self::MEDIA_OBJECT_ID)));
        $this->project(new PlaceImageAdded(self::PLACE_ID, $this->image(self::MEDIA_OBJECT_ID)));
        $this->project(
            new OrganizerImageAdded(self::ORGANIZER_ID, self::MEDIA_OBJECT_ID, 'nl', 'description', 'copyright')
        );

        $this->assertEquals(
            [
                $this->relation(ItemType::event(), self::EVENT_ID),
                $this->relation(ItemType::place(), self::PLACE_ID),
                $this->relation(ItemType::organizer(), self::ORGANIZER_ID),
            ],
            $this->repository->getByMediaObject(new Uuid(self::MEDIA_OBJECT_ID))
        );
    }

    /**
     * @test
     */
    public function it_removes_a_relation_when_an_image_is_removed(): void
    {
        $this->project(new EventImageAdded(self::EVENT_ID, $this->image(self::MEDIA_OBJECT_ID)));
        $this->project(
            new OrganizerImageAdded(self::ORGANIZER_ID, self::MEDIA_OBJECT_ID, 'nl', 'description', 'copyright')
        );

        $this->project(new EventImageRemoved(self::EVENT_ID, $this->image(self::MEDIA_OBJECT_ID)));
        $this->project(new OrganizerImageRemoved(self::ORGANIZER_ID, self::MEDIA_OBJECT_ID));

        $this->assertEquals([], $this->repository->getByMediaObject(new Uuid(self::MEDIA_OBJECT_ID)));
    }

    /**
     * @test
     */
    public function it_replaces_the_relations_of_an_item_on_images_from_udb2(): void
    {
        $this->project(new EventImageAdded(self::EVENT_ID, $this->image(self::MEDIA_OBJECT_ID)));

        $this->project(
            new EventImagesUpdatedFromUDB2(
                self::EVENT_ID,
                ImageCollection::fromArray([$this->image(self::OTHER_MEDIA_OBJECT_ID)])
            )
        );

        $this->assertEquals(
            [new MediaObjectRelation(new Uuid(self::OTHER_MEDIA_OBJECT_ID), ItemType::event(), self::EVENT_ID)],
            $this->repository->getByItem(self::EVENT_ID)
        );
    }

    /**
     * @test
     */
    public function it_copies_the_relations_of_the_original_event(): void
    {
        $this->project(new EventImageAdded(self::EVENT_ID, $this->image(self::MEDIA_OBJECT_ID)));

        $this->project(
            new EventCopied(self::COPIED_EVENT_ID, self::EVENT_ID, new PermanentCalendar(new OpeningHours()))
        );

        $this->assertEquals(
            [
                $this->relation(ItemType::event(), self::EVENT_ID),
                $this->relation(ItemType::event(), self::COPIED_EVENT_ID),
            ],
            $this->repository->getByMediaObject(new Uuid(self::MEDIA_OBJECT_ID))
        );
    }

    /**
     * @test
     */
    public function it_removes_the_relations_of_a_deleted_item(): void
    {
        $this->project(new EventImageAdded(self::EVENT_ID, $this->image(self::MEDIA_OBJECT_ID)));
        $this->project(new PlaceImageAdded(self::PLACE_ID, $this->image(self::MEDIA_OBJECT_ID)));
        $this->project(
            new OrganizerImageAdded(self::ORGANIZER_ID, self::MEDIA_OBJECT_ID, 'nl', 'description', 'copyright')
        );

        $this->project(new EventDeleted(self::EVENT_ID));
        $this->project(new OrganizerDeleted(self::ORGANIZER_ID));

        $this->assertEquals(
            [$this->relation(ItemType::place(), self::PLACE_ID)],
            $this->repository->getByMediaObject(new Uuid(self::MEDIA_OBJECT_ID))
        );
    }

    private function project(object $event): void
    {
        $this->projector->handle(new DomainMessage('id', 0, new Metadata(), $event, DateTime::now()));
    }

    private function relation(ItemType $itemType, string $itemId): MediaObjectRelation
    {
        return new MediaObjectRelation(new Uuid(self::MEDIA_OBJECT_ID), $itemType, $itemId);
    }

    private function image(string $mediaObjectId): Image
    {
        return new Image(
            new Uuid($mediaObjectId),
            new MIMEType('image/png'),
            new Description('description'),
            new CopyrightHolder('copyright'),
            new Url('https://images.uitdatabank.local/' . $mediaObjectId . '.png'),
            new Language('nl')
        );
    }
}
