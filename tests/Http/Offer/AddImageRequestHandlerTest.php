<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\AddImage as EventAddImage;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Place\Commands\AddImage as PlaceAddImage;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;

final class AddImageRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const MEDIA_ID = '0d24c18a-0bd5-46c1-b331-1fa38012bded';

    private TraceableCommandBus $commandBus;

    private AddImageRequestHandler $addImageRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->addImageRequestHandler = new AddImageRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_adding_an_image_to_an_offer(
        string $offerType,
        AbstractAddImage $addImage
    ): void {
        $addImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withBodyFromString(
                '{ "mediaObjectId": "' . self::MEDIA_ID . '" }'
            )
            ->build('POST');

        $response = $this->addImageRequestHandler->handle($addImageRequest);

        $this->assertEquals(
            [
                $addImage,
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
        string $offerType,
        AbstractAddImage $addImage
    ): void {
        $addImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withBodyFromString(
                '{}'
            )
            ->build('POST');

        $response = $this->addImageRequestHandler->handle($addImageRequest);

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
        return [
            [
                'offerType' => 'events',
                'addImage' => new EventAddImage(
                    self::OFFER_ID,
                    new UUID(self::MEDIA_ID)
                ),
            ],
            [
                'offerType' => 'places',
                'addImage' => new PlaceAddImage(
                    self::OFFER_ID,
                    new UUID(self::MEDIA_ID)
                ),
            ],
        ];
    }
}
