<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\RemoveImage as EventRemoveImage;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractRemoveImage;
use CultuurNet\UDB3\Place\Commands\RemoveImage as PlaceRemoveImage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RemoveImageRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const MEDIA_ID = '0d24c18a-0bd5-46c1-b331-1fa38012bded';

    private TraceableCommandBus $commandBus;

    private MediaManagerInterface&MockObject $mediaManager;

    private RemoveImageRequestHandler $removeImageRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    private Image $image;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->mediaManager = $this->createMock(
            MediaManagerInterface::class
        );

        $this->removeImageRequestHandler = new RemoveImageRequestHandler(
            $this->commandBus,
            $this->mediaManager
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->image = new Image(
            new Uuid(self::MEDIA_ID),
            MIMEType::fromSubtype('png'),
            new Description('A picture of a picture'),
            new CopyrightHolder('CreativeCommons'),
            new Url('https://url.com/image.jpeg'),
            new Language('nl')
        );

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_removing_the_image_of_an_offer(
        string $offerType,
        AbstractRemoveImage $removeImage
    ): void {
        $this->mediaManager
            ->method('getImage')
            ->with(new Uuid(self::MEDIA_ID))
            ->willReturn($this->image);

        $removeImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('mediaId', self::MEDIA_ID)
            ->build('DELETE');

        $response = $this->removeImageRequestHandler->handle($removeImageRequest);

        $this->assertEquals(
            [
                $removeImage,
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    public function offerTypeDataProvider(): array
    {
        $image = new Image(
            new Uuid(self::MEDIA_ID),
            MIMEType::fromSubtype('png'),
            new Description('A picture of a picture'),
            new CopyrightHolder('CreativeCommons'),
            new Url('https://url.com/image.jpeg'),
            new Language('nl')
        );
        return [
            [
                'offerType' => 'events',
                'removeImage' => new EventRemoveImage(
                    self::OFFER_ID,
                    $image
                ),
            ],
            [
                'offerType' => 'places',
                'removeImage' => new PlaceRemoveImage(
                    self::OFFER_ID,
                    $image
                ),
            ],
        ];
    }
}
