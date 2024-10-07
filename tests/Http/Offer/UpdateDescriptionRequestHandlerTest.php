<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateDescription as EventUpdateDescription;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateDescription;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use CultuurNet\UDB3\Place\Commands\UpdateDescription as PlaceUpdateDescription;
use PHPUnit\Framework\TestCase;

final class UpdateDescriptionRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private UpdateDescriptionRequestHandler $updateDescriptionRequestHandler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->updateDescriptionRequestHandler = new UpdateDescriptionRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider validRequestsProvider
     * @param AbstractUpdateDescription|DeleteDescription $descriptionCommand
     */
    public function it_handles_updating_the_description_of_an_offer(
        string $offerType,
        string $request,
        $descriptionCommand
    ): void {
        $updateDescriptionRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('language', 'en')
            ->withBodyFromString($request)
            ->build('PUT');

        $response = $this->updateDescriptionRequestHandler->handle($updateDescriptionRequest);

        $this->assertEquals(
            [
                $descriptionCommand,
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    public function validRequestsProvider(): array
    {
        return [
            [
                'offerType' => 'events',
                'request' => '{"description": "Some info about the offer"}',
                'updateDescription' => new EventUpdateDescription(
                    self::OFFER_ID,
                    new Language('en'),
                    new Description('Some info about the offer')
                ),
            ],
            [
                'offerType' => 'places',
                'request' => '{"description": "Some info about the offer"}',
                'updateDescription' => new PlaceUpdateDescription(
                    self::OFFER_ID,
                    new Language('en'),
                    new Description('Some info about the offer')
                ),
            ],
            [
                'offerType' => 'events',
                'request' => '{"description": ""}',
                'updateDescription' => new DeleteDescription(
                    self::OFFER_ID,
                    new Language('en')
                ),
            ],
            [
                'offerType' => 'places',
                'request' => '{"description": ""}',
                'updateDescription' => new DeleteDescription(
                    self::OFFER_ID,
                    new Language('en')
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidBodyDataProvider
     */
    public function it_throws_an_api_problem_for_an_invalid_body(string $body, ApiProblem $expectedApiProblem): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withRouteParameter('offerType', 'events')
            ->withRouteParameter('language', 'nl')
            ->withBodyFromString($body)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->updateDescriptionRequestHandler->handle($request)
        );
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (description) are missing')
                ),
            ],
            [
                '{"description": 1}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/description', 'The data (integer) must match the type: string')
                ),
            ],
        ];
    }
}
