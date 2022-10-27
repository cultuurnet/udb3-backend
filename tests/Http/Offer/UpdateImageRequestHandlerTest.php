<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateImage as EventUpdateImage;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractUpdateImage;
use CultuurNet\UDB3\Place\Commands\UpdateImage as PlaceUpdateImage;
use CultuurNet\UDB3\StringLiteral;
use PHPUnit\Framework\TestCase;

class UpdateImageRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    use AssertApiProblemTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const MEDIA_ID = '0d24c18a-0bd5-46c1-b331-1fa38012bded';

    private TraceableCommandBus $commandBus;

    private UpdateImageRequestHandler $updateImageRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateImageRequestHandler = new UpdateImageRequestHandler(
            $this->commandBus
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_handles_updating_an_image_of_an_offer(
        string $offerType,
        AbstractUpdateImage $updateImage
    ): void {
        $updateImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('mediaId', self::MEDIA_ID)
            ->withJsonBodyFromArray(
                [
                    'description' => 'A new picture of a picture',
                    'copyrightHolder' => 'Public Domain',
                ]
            )
            ->build('PUT');

        $response = $this->updateImageRequestHandler->handle($updateImageRequest);

        $this->assertEquals(
            [
                $updateImage,
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
    public function it_throws_an_api_problem_when_the_image_is_not_a_uuid(string $offerType): void
    {
        $unknownImage = 'not-an-image';
        $updateImageRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('mediaId', $unknownImage)
            ->withJsonBodyFromArray(
                [
                    'description' => 'A new picture of a picture',
                    'copyrightHolder' => 'Public Domain',
                ]
            )
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::imageNotFound($unknownImage),
            fn () => $this->updateImageRequestHandler->handle($updateImageRequest)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    public function offerTypeDataProvider(): array
    {
        return [
            [
                'offerType' => 'events',
                'updateImage' => new EventUpdateImage(
                    self::OFFER_ID,
                    new UUID(self::MEDIA_ID),
                    new StringLiteral('A new picture of a picture'),
                    new CopyrightHolder('Public Domain')
                ),
            ],
            [
                'offerType' => 'places',
                'updateImage' => new PlaceUpdateImage(
                    self::OFFER_ID,
                    new UUID(self::MEDIA_ID),
                    new StringLiteral('A new picture of a picture'),
                    new CopyrightHolder('Public Domain')
                ),
            ],
        ];
    }
}
