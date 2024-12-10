<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\SelectMainImage as EventSelectMainImage;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use CultuurNet\UDB3\Offer\ImageMustBeLinkedException;
use CultuurNet\UDB3\Place\Commands\SelectMainImage as PlaceSelectMainImage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SelectMainImageRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const MEDIA_ID = '0d24c18a-0bd5-46c1-b331-1fa38012bded';

    private TraceableCommandBus $commandBus;

    /**
     * @var MediaManagerInterface&MockObject
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
    public function it_handles_selecting_the_main_image_of_an_offer(
        string $offerType,
        AbstractSelectMainImage $selectMainImage
    ): void {
        $this->givenThereIsAnImage();

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
     * @dataProvider invalidRequestBodyProvider
     */
    public function it_throws_on_missing_media_id(string $body, ApiProblem $expectedProblem): void
    {
        $selectMainImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withBodyFromString($body)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            $expectedProblem,
            fn () => $this->selectMainImageRequestHandler->handle($selectMainImageRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_image_does_not_exist(): void
    {
        $this->mediaManager
            ->method('getImage')
            ->with(new Uuid(self::MEDIA_ID))
            ->willThrowException(new MediaObjectNotFoundException());

        $selectMainImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(['mediaObjectId' => self::MEDIA_ID])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::imageNotFound(self::MEDIA_ID),
            fn () => $this->selectMainImageRequestHandler->handle($selectMainImageRequest)
        );
    }

    /**
     * @test
     */
    public function it_throws_when_image_is_not_linked_to_offer(): void
    {
        $this->givenThereIsAnImage();

        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(new ImageMustBeLinkedException());

        $handler = new SelectMainImageRequestHandler(
            $commandBus,
            $this->mediaManager
        );

        $selectMainImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(['mediaObjectId' => self::MEDIA_ID])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::imageMustBeLinkedToResource(self::MEDIA_ID),
            fn () => $handler->handle($selectMainImageRequest)
        );
    }

    private function givenThereIsAnImage(): void
    {
        $this->mediaManager
            ->method('getImage')
            ->with(new Uuid(self::MEDIA_ID))
            ->willReturn($this->image);
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

    public function invalidRequestBodyProvider(): array
    {
        return [
            [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (mediaObjectId) are missing')
                ),
            ],
            [
                '{"mediaObjectId": "not-a-uuid"}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/mediaObjectId', 'The data must match the \'uuid\' format')
                ),
            ],
        ];
    }
}
