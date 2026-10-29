<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Events\ImageUpdated as EventImageUpdated;
use CultuurNet\UDB3\Media\Commands\UpdateMediaObjectAiGenerated;
use CultuurNet\UDB3\Media\Events\MediaObjectAiGeneratedUpdated;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\ReadModel\Relations\InMemoryMediaObjectRelationsRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\Image\UpdateImageAiGenerated as UpdateOfferImageAiGenerated;
use CultuurNet\UDB3\Organizer\Commands\UpdateImageAiGenerated as UpdateOrganizerImageAiGenerated;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated as OrganizerImageUpdated;
use CultuurNet\UDB3\Place\Events\ImageUpdated as PlaceImageUpdated;
use PHPUnit\Framework\TestCase;

final class AiGeneratedImageProcessManagerTest extends TestCase
{
    private const IMAGE_ID = 'c263ce95-44b4-41b0-916f-ce72063b929b';
    private const EVENT_ID = '1d4f5a1c-6f1a-4a7c-9a3e-2d1b0c3e8f01';
    private const PLACE_ID = '2e5a6b2d-7a2b-4b8d-8b4f-3e2c1d4f9a02';
    private const ORGANIZER_ID = '3f6b7c3e-8b3c-4c9e-9c5a-4f3d2e5a0b03';

    private TraceableCommandBus $commandBus;

    private InMemoryMediaObjectRelationsRepository $relationsRepository;

    private AiGeneratedImageProcessManager $processManager;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->relationsRepository = new InMemoryMediaObjectRelationsRepository();

        $this->processManager = new AiGeneratedImageProcessManager($this->commandBus, $this->relationsRepository);
    }

    /**
     * @test
     * @dataProvider imageUpdatedProvider
     */
    public function it_updates_the_media_object_when_ai_generated_of_an_item_image_is_updated(object $imageUpdated): void
    {
        $this->handle($imageUpdated);

        $this->assertEquals(
            [new UpdateMediaObjectAiGenerated(new Uuid(self::IMAGE_ID), true)],
            $this->commandBus->getRecordedCommands()
        );
    }

    public function imageUpdatedProvider(): array
    {
        return [
            'event' => [
                new EventImageUpdated(self::EVENT_ID, self::IMAGE_ID, 'Beschrijving', 'Rechtenhouder', 'nl', true),
            ],
            'place' => [
                new PlaceImageUpdated(self::PLACE_ID, self::IMAGE_ID, 'Beschrijving', 'Rechtenhouder', 'nl', true),
            ],
            'organizer' => [
                new OrganizerImageUpdated(self::ORGANIZER_ID, self::IMAGE_ID, 'nl', 'Beschrijving', 'Rechtenhouder', true),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_does_not_update_the_media_object_when_ai_generated_is_not_known(): void
    {
        $this->handle(new EventImageUpdated(self::EVENT_ID, self::IMAGE_ID, 'Beschrijving', 'Rechtenhouder'));

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_updates_all_related_items_when_ai_generated_of_the_media_object_is_updated(): void
    {
        $this->relationsRepository->add(self::IMAGE_ID, ItemType::event(), self::EVENT_ID);
        $this->relationsRepository->add(self::IMAGE_ID, ItemType::place(), self::PLACE_ID);
        $this->relationsRepository->add(self::IMAGE_ID, ItemType::organizer(), self::ORGANIZER_ID);

        $this->handle(
            new MediaObjectAiGeneratedUpdated(
                new Uuid(self::IMAGE_ID),
                new Description('Beschrijving'),
                new Language('nl'),
                false
            )
        );

        $this->assertEquals(
            [
                new UpdateOfferImageAiGenerated(self::EVENT_ID, new Uuid(self::IMAGE_ID), false),
                new UpdateOfferImageAiGenerated(self::PLACE_ID, new Uuid(self::IMAGE_ID), false),
                new UpdateOrganizerImageAiGenerated(self::ORGANIZER_ID, new Uuid(self::IMAGE_ID), false),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    private function handle(object $event): void
    {
        $this->processManager->handle(
            new DomainMessage('id', 0, new Metadata(), $event, DateTime::now())
        );
    }
}
