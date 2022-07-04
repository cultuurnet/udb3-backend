<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class UpdateOrganizerRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const OFFER_ID = 'cc5b0af2-1cb8-4a1f-9f9f-99a8cdbd8895';
    private const ORGANIZER_ID = '10cfb8c6-8e2e-4fdb-8119-5b9057cf33d7';

    private TraceableCommandBus $commandBus;
    private UpdateOrganizerRequestHandler $updateOrganizerRequestHandler;

    protected function setUp(): void
    {
        $organizerRepository = new InMemoryDocumentRepository();
        $organizerRepository->save(
            new JsonDocument(self::ORGANIZER_ID, '{}')
        );

        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->updateOrganizerRequestHandler = new UpdateOrganizerRequestHandler(
            $this->commandBus,
            $organizerRepository
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_if_the_organizer_id_is_malformed(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('organizerId', '%20%20' . self::ORGANIZER_ID)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound(
                'Organizer with id "%20%2010cfb8c6-8e2e-4fdb-8119-5b9057cf33d7" does not exist.'
            ),
            fn () => $this->updateOrganizerRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_throws_an_api_problem_if_the_organizer_does_not_exist(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('organizerId', '2c99803f-7f6b-485c-bfe0-a65a4e4abf71')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound(
                'Organizer with id "2c99803f-7f6b-485c-bfe0-a65a4e4abf71" does not exist.'
            ),
            fn () => $this->updateOrganizerRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider offerTypeDataProvider
     */
    public function it_dispatches_an_update_organizer_command(string $offerType): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('offerType', $offerType)
            ->withRouteParameter('offerId', self::OFFER_ID)
            ->withRouteParameter('organizerId', self::ORGANIZER_ID)
            ->build('PUT');

        $response = $this->updateOrganizerRequestHandler->handle($request);

        $this->assertEquals(
            [
                new UpdateOrganizer(
                    self::OFFER_ID,
                    self::ORGANIZER_ID
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
        $this->assertInstanceOf(NoContentResponse::class, $response);
    }

    public function offerTypeDataProvider(): array
    {
        return [
            ['events'],
            ['places'],
        ];
    }
}
