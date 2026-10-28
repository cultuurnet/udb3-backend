<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Media;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Events\ImageAdded as EventImageAdded;
use CultuurNet\UDB3\Media\Commands\RenameImage;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Events\ImageAdded as OrganizerImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated as OrganizerImageUpdated;
use CultuurNet\UDB3\Place\Events\ImageUpdated as PlaceImageUpdated;
use PHPUnit\Framework\TestCase;

final class AiGeneratedImageRenamerTest extends TestCase
{
    private const IMAGE_ID = 'de305d54-75b4-431b-adb2-eb6b9e546014';

    private TraceableCommandBus $commandBus;

    private AiGeneratedImageRenamer $renamer;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->renamer = new AiGeneratedImageRenamer($this->commandBus);
    }

    /**
     * @test
     * @dataProvider imageEventProvider
     */
    public function it_renames_the_image_when_ai_generated_is_known(object $event, array $expectedCommands): void
    {
        $this->renamer->handle(DomainMessage::recordNow('2b7a4a64-4a9c-4a4e-8e0b-2b1c3d4e5f60', 1, new Metadata(), $event));

        $this->assertEquals($expectedCommands, $this->commandBus->getRecordedCommands());
    }

    public function imageEventProvider(): array
    {
        $itemId = '2b7a4a64-4a9c-4a4e-8e0b-2b1c3d4e5f60';
        $rename = fn (bool $isAiGenerated) => [new RenameImage(new Uuid(self::IMAGE_ID), $isAiGenerated)];

        return [
            'offer image added as ai generated' => [
                new EventImageAdded($itemId, $this->createImage(true)),
                $rename(true),
            ],
            'offer image added with unknown ai generated' => [
                new EventImageAdded($itemId, $this->createImage(null)),
                [],
            ],
            'offer image updated as not ai generated' => [
                new PlaceImageUpdated($itemId, self::IMAGE_ID, 'description', 'copyright', null, false),
                $rename(false),
            ],
            'offer image updated with unknown ai generated' => [
                new PlaceImageUpdated($itemId, self::IMAGE_ID, 'description', 'copyright'),
                [],
            ],
            'organizer image added as ai generated' => [
                new OrganizerImageAdded($itemId, self::IMAGE_ID, 'nl', 'description', 'copyright', true),
                $rename(true),
            ],
            'organizer image updated as not ai generated' => [
                new OrganizerImageUpdated($itemId, self::IMAGE_ID, 'nl', 'description', 'copyright', false),
                $rename(false),
            ],
            'organizer image updated with unknown ai generated' => [
                new OrganizerImageUpdated($itemId, self::IMAGE_ID, 'nl', 'description', 'copyright'),
                [],
            ],
        ];
    }

    private function createImage(?bool $isAiGenerated): Image
    {
        return new Image(
            new Uuid(self::IMAGE_ID),
            new MIMEType('image/png'),
            new Description('description'),
            new CopyrightHolder('copyright'),
            new Url('https://images.uitdatabank.dev/' . self::IMAGE_ID . '.png'),
            new Language('nl'),
            $isAiGenerated
        );
    }
}
