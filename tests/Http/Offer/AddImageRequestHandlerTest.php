<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Event\Commands\AddImage as EventAddImage;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Place\Commands\AddImage as PlaceAddImage;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AddImageRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const MEDIA_ID = '0d24c18a-0bd5-46c1-b331-1fa38012bded';

    private TraceableCommandBus $commandBus;

    private AddImageRequestHandler $addImageRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    private Repository&MockObject $imageRepository;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->imageRepository = $this->createMock(Repository::class);

        $this->addImageRequestHandler = new AddImageRequestHandler(
            $this->commandBus,
            $this->imageRepository,
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

        $this->imageRepository->method('load')
            ->with(self::MEDIA_ID)
            ->willReturn(
                MediaObject::create(
                    new Uuid(self::MEDIA_ID),
                    MIMEType::fromSubtype('jpeg'),
                    new Description('Uploaded image'),
                    new CopyrightHolder('madewithlove'),
                    new Url('https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.jpg'),
                    new Language('nl')
                )
            );

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
     * @dataProvider invalidBodyDataProvider
     */
    public function it_throws_on_missing_media_id(string $body, ApiProblem $expectedProblem): void
    {
        $addImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withBodyFromString($body)
            ->build('POST');

        $this->assertCallableThrowsApiProblem(
            $expectedProblem,
            fn () => $this->addImageRequestHandler->handle($addImageRequest)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_when_image_not_found(string $offerType): void
    {
        $unknownImageId = '08805a3c-ffe0-4c94-a1bc-453a6dd9d01f';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withJsonBodyFromArray(['mediaObjectId' => $unknownImageId])
            ->build('POST');

        $this->imageRepository->method('load')
            ->with($unknownImageId)
            ->willThrowException(new AggregateNotFoundException());

        $this->assertCallableThrowsApiProblem(
            ApiProblem::imageNotFound($unknownImageId),
            fn () => $this->addImageRequestHandler->handle($request)
        );
    }

    public function offerTypeDataProvider(): array
    {
        return [
            [
                'offerType' => 'events',
                'addImage' => new EventAddImage(
                    self::OFFER_ID,
                    new Uuid(self::MEDIA_ID)
                ),
            ],
            [
                'offerType' => 'places',
                'addImage' => new PlaceAddImage(
                    self::OFFER_ID,
                    new Uuid(self::MEDIA_ID)
                ),
            ],
        ];
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (mediaObjectId) are missing')
                ),
            ],
            [
                '{"mediaObjectId": 1}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/mediaObjectId', 'The data (integer) must match the type: string')
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
