<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media\ReadModel\Relations;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Events\EventCopied;
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
use CultuurNet\UDB3\Place\Events\Image\ImagesImportedFromUDB2 as PlaceImagesImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\ImageAdded as PlaceImageAdded;
use PHPUnit\Framework\TestCase;

final class MediaObjectRelationsProjectorTest extends TestCase
{
    private const IMAGE_ID = 'c263ce95-44b4-41b0-916f-ce72063b929b';
    private const OTHER_IMAGE_ID = 'e717473d-609d-4bf1-a008-c935c8836335';
    private const EVENT_ID = '1d4f5a1c-6f1a-4a7c-9a3e-2d1b0c3e8f01';
    private const PLACE_ID = '2e5a6b2d-7a2b-4b8d-8b4f-3e2c1d4f9a02';
    private const ORGANIZER_ID = '3f6b7c3e-8b3c-4c9e-9c5a-4f3d2e5a0b03';

    private InMemoryMediaObjectRelationsRepository $repository;

    private MediaObjectRelationsProjector $projector;

    protected function setUp(): void
    {
        $this->repository = new InMemoryMediaObjectRelationsRepository();
        $this->projector = new MediaObjectRelationsProjector($this->repository);
    }

    /**
     * @test
     */
    public function it_adds_relations_when_images_are_added(): void
    {
        $this->handle(new EventImageAdded(self::EVENT_ID, $this->image(self::IMAGE_ID)));
        $this->handle(new PlaceImageAdded(self::PLACE_ID, $this->image(self::IMAGE_ID)));
        $this->handle(new OrganizerImageAdded(self::ORGANIZER_ID, self::IMAGE_ID, 'nl', 'Beschrijving', 'Rechtenhouder'));

        $this->assertEquals(
            [
                new MediaObjectRelation(ItemType::event(), self::EVENT_ID),
                new MediaObjectRelation(ItemType::place(), self::PLACE_ID),
                new MediaObjectRelation(ItemType::organizer(), self::ORGANIZER_ID),
            ],
            $this->repository->getRelations(self::IMAGE_ID)
        );
    }

    /**
     * @test
     */
    public function it_removes_relations_when_images_are_removed(): void
    {
        $this->handle(new EventImageAdded(self::EVENT_ID, $this->image(self::IMAGE_ID)));
        $this->handle(new OrganizerImageAdded(self::ORGANIZER_ID, self::IMAGE_ID, 'nl', 'Beschrijving', 'Rechtenhouder'));

        $this->handle(new EventImageRemoved(self::EVENT_ID, $this->image(self::IMAGE_ID)));
        $this->handle(new OrganizerImageRemoved(self::ORGANIZER_ID, self::IMAGE_ID));

        $this->assertEquals([], $this->repository->getRelations(self::IMAGE_ID));
    }

    /**
     * @test
     */
    public function it_replaces_relations_when_images_are_imported_or_updated_from_udb2(): void
    {
        $this->handle(new PlaceImagesImportedFromUDB2(self::PLACE_ID, new ImageCollection($this->image(self::IMAGE_ID))));
        $this->handle(new EventImageAdded(self::EVENT_ID, $this->image(self::IMAGE_ID)));
        $this->handle(new EventImagesUpdatedFromUDB2(self::EVENT_ID, new ImageCollection($this->image(self::OTHER_IMAGE_ID))));

        $this->assertEquals(
            [new MediaObjectRelation(ItemType::place(), self::PLACE_ID)],
            $this->repository->getRelations(self::IMAGE_ID)
        );
        $this->assertEquals(
            [new MediaObjectRelation(ItemType::event(), self::EVENT_ID)],
            $this->repository->getRelations(self::OTHER_IMAGE_ID)
        );
    }

    /**
     * @test
     */
    public function it_copies_relations_when_an_event_is_copied(): void
    {
        $copyId = '4a7c8d4f-9c4d-4dae-8d6b-5a4e3f6b1c04';

        $this->handle(new EventImageAdded(self::EVENT_ID, $this->image(self::IMAGE_ID)));
        $this->handle(new EventCopied($copyId, self::EVENT_ID, new PermanentCalendar(new OpeningHours())));

        $this->assertEquals(
            [
                new MediaObjectRelation(ItemType::event(), self::EVENT_ID),
                new MediaObjectRelation(ItemType::event(), $copyId),
            ],
            $this->repository->getRelations(self::IMAGE_ID)
        );
    }

    private function handle(object $event): void
    {
        $this->projector->handle(
            new DomainMessage('id', 0, new Metadata(), $event, DateTime::now())
        );
    }

    private function image(string $id): Image
    {
        return new Image(
            new Uuid($id),
            new MIMEType('image/png'),
            new Description('Beschrijving'),
            new CopyrightHolder('Rechtenhouder'),
            new Url('https://images.uitdatabank.local/' . $id . '.png'),
            new Language('nl')
        );
    }
}
