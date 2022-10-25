<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Commands\UpdateDescription as EventUpdateDescription;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateDescription as PlaceUpdateDescription;
use PHPUnit\Framework\TestCase;

final class UpdateDescriptionRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private const OFFER_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';
    private const DESCRIPTION = 'Some info about the offer';

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
     * @dataProvider descriptionProvider
     */
    public function it_handles_updating_the_description_of_an_offer(
        string $offerType,
        AbstractUpdateDescription $updateDescription
    ): void {
        $updateDescriptionRequest = $this->psr7RequestBuilder
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('language', 'en')
            ->withJsonBodyFromArray([
                'description' => self::DESCRIPTION,
            ])
            ->build('PUT');

        $response = $this->updateDescriptionRequestHandler->handle($updateDescriptionRequest);

        $this->assertEquals(
            [
                $updateDescription,
            ],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(
            new NoContentResponse(),
            $response
        );
    }

    public function descriptionProvider(): array
    {
        return [
            [
                'offerType' => 'events',
                'updateDescription' => new EventUpdateDescription(
                    self::OFFER_ID,
                    new Language('en'),
                    new Description(self::DESCRIPTION)
                ),
            ],
            [
                'offerType' => 'places',
                'updateDescription' => new PlaceUpdateDescription(
                    self::OFFER_ID,
                    new Language('en'),
                    new Description(self::DESCRIPTION)
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
            [
                '{"description": ""}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/description', 'Minimum string length is 1, found 0')
                ),
            ],
            [
                '{"description": "     "}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/description', 'The string should match pattern: \S')
                ),
            ],
        ];
    }
}
