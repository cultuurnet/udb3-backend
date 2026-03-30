<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateDeparturePlaces;
use CultuurNet\UDB3\Event\IncompatibleAudienceType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class UpdateDeparturePlacesRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';
    private const EXISTING_PLACE_ID = '5a0b4a1e-2a3b-4c4d-8e5f-6a7b8c9d0e1f';
    private const EXISTING_PLACE_ID_2 = '1b2c3d4e-5f6a-7b8c-9d0e-1f2a3b4c5d6e';
    private const BASE_URL = 'http://io.uitdatabank.local:80';

    private TraceableCommandBus $commandBus;
    private LoggerInterface&MockObject $logger;
    private UpdateDeparturePlacesRequestHandler $handler;
    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $placeRepository = new InMemoryDocumentRepository();
        $placeRepository->save(new JsonDocument(self::EXISTING_PLACE_ID, '{}'));
        $placeRepository->save(new JsonDocument(self::EXISTING_PLACE_ID_2, '{}'));

        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->handler = new UpdateDeparturePlacesRequestHandler(
            $this->commandBus,
            $placeRepository,
            new IriOfferIdentifierFactory(
                'http://io\\.uitdatabank\\.local\\:80/(?<offertype>[event|place]+)/(?<offerid>[a-zA-Z0-9\\-]+)'
            ),
            new DeparturePlacesLimitLogger($this->logger),
        );

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_dispatches_update_departure_places_with_valid_urls(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([
                self::BASE_URL . '/place/' . self::EXISTING_PLACE_ID,
                self::BASE_URL . '/place/' . self::EXISTING_PLACE_ID_2,
            ])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(NoContentResponse::class, $response);
        $this->assertEquals(
            [
                new UpdateDeparturePlaces(
                    self::EVENT_ID,
                    new Urls(
                        new Url(self::BASE_URL . '/place/' . self::EXISTING_PLACE_ID),
                        new Url(self::BASE_URL . '/place/' . self::EXISTING_PLACE_ID_2),
                    )
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_dispatches_update_departure_places_with_empty_array(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(NoContentResponse::class, $response);
        $this->assertEquals(
            [new UpdateDeparturePlaces(self::EVENT_ID, new Urls())],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_for_non_existing_place(): void
    {
        $nonExistingUrl = self::BASE_URL . '/place/00000000-0000-0000-0000-000000000000';

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([$nonExistingUrl])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/0', 'The place with url "' . $nonExistingUrl . '" was not found.')
            ),
            fn () => $this->handler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_for_invalid_place_url(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray(['https://example.com/not-a-place'])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/0',
                    'The string should match pattern: ^http[s]?:\/\/.+?\/place[s]?\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\/]?'
                )
            ),
            fn () => $this->handler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_when_audience_type_is_incompatible(): void
    {
        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(IncompatibleAudienceType::forDeparturePlaces(self::EVENT_ID));

        $placeRepository = new InMemoryDocumentRepository();
        $placeRepository->save(new JsonDocument(self::EXISTING_PLACE_ID, '{}'));

        $handler = new UpdateDeparturePlacesRequestHandler(
            $commandBus,
            $placeRepository,
            new IriOfferIdentifierFactory(
                'http://io\\.uitdatabank\\.local\\:80/(?<offertype>[event|place]+)/(?<offerid>[a-zA-Z0-9\\-]+)'
            ),
            new DeparturePlacesLimitLogger($this->logger),
        );

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray([
                self::BASE_URL . '/place/' . self::EXISTING_PLACE_ID,
            ])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::inCompatibleAudienceType(
                'Departure places can only be set on events with audienceType "childrenOnly". Event: ' . self::EVENT_ID
            ),
            fn () => $handler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider invalidBody
     */
    public function it_throws_an_api_problem_for_an_invalid_body(string $body, ApiProblem $expectedApiProblem): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withBodyFromString($body)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->handler->handle($request)
        );
    }

    public function invalidBody(): array
    {
        return [
            'missing body' => [
                '',
                ApiProblem::bodyMissing(),
            ],
            'invalid syntax' => [
                '{{}',
                ApiProblem::bodyInvalidSyntax('JSON'),
            ],
            'not an array' => [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The data (object) must match the type: array')
                ),
            ],
        ];
    }

    /**
     * @test
     */
    public function it_logs_an_error_when_departure_places_limit_is_exceeded(): void
    {
        $places = array_map(
            fn (int $i) => self::BASE_URL . '/place/' . sprintf('00000000-0000-0000-0000-%012d', $i),
            range(1, 21)
        );

        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray($places)
            ->build('PUT');

        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Departure places limit exceeded for event ' . self::EVENT_ID));

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'Array should have at most 20 items, 21 found')
            ),
            fn () => $this->handler->handle($request)
        );
    }
}
