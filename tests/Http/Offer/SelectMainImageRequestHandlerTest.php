<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\SelectMainImage as EventSelectMainImage;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use CultuurNet\UDB3\Place\Commands\SelectMainImage as PlaceSelectMainImage;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SelectMainImageRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const MEDIA_ID = '0d24c18a-0bd5-46c1-b331-1fa38012bded';

    private TraceableCommandBus $commandBus;

    /**
     * @var MediaManagerInterface|MockObject
     */
    private $mediaManager;

    private SelectMainImageRequestHandler $selectMainImageRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    private Image $image;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->mediaManager = $this->createMock(
            MediaManagerInterface::class
        );

        $this->selectMainImageRequestHandler = new SelectMainImageRequestHandler(
            $this->commandBus,
            $this->mediaManager
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();


        $this->image = new Image(
            new UUID(self::MEDIA_ID),
            MIMEType::fromSubtype('png'),
            new Description('A picture of a pictur'),
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
    public function it_handles_selecting_the_main_image_of_an_offer(
        string $offerType,
        AbstractSelectMainImage $selectMainImage
    ): void {
        $this->mediaManager
            ->method('getImage')
            ->with(new UUID(self::MEDIA_ID))
            ->willReturn($this->image);


        $selectMainImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withBodyFromString(
                '{ "mediaObjectId": "' . self::MEDIA_ID . '" }'
            )
            ->build('PUT');

        $response = $this->selectMainImageRequestHandler->handle($selectMainImageRequest);

        $this->assertEquals(
            [
                $selectMainImage,
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_on_missing_media_id(
        string $offerType
    ): void {
        $selectMainImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withBodyFromString(
                '{}'
            )
            ->build('POST');

        $response = $this->selectMainImageRequestHandler->handle($selectMainImageRequest);

        $this->assertJsonResponse(
            new JsonResponse(
                ['error' => 'media object id required'],
                StatusCodeInterface::STATUS_BAD_REQUEST
            ),
            $response
        );
    }

    public function offerTypeDataProvider(): array
    {
        $image = new Image(
            new UUID(self::MEDIA_ID),
            MIMEType::fromSubtype('png'),
            new Description('A picture of a pictur'),
            new CopyrightHolder('CreativeCommons'),
            new Url('https://url.com/image.jpeg'),
            new Language('nl')
        );
        return [
            [
                'offerType' => 'events',
                'selectMainImage' => new EventSelectMainImage(
                    self::OFFER_ID,
                    $image
                ),
            ],
            [
                'offerType' => 'places',
                'selectMainImage' => new PlaceSelectMainImage(
                    self::OFFER_ID,
                    $image
                ),
            ],
        ];
    }
}
