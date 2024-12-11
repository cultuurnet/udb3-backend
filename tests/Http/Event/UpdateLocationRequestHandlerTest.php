<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class UpdateLocationRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EXISTING_PLACE_ID = '2ee2089d-35ce-4ae6-93f9-c7a0d08bdb1e';

    private TraceableCommandBus $commandBus;
    private UpdateLocationRequestHandler $updateLocationRequestHandler;

    protected function setUp(): void
    {
        $locationRepository = new InMemoryDocumentRepository();
        $locationRepository->save(
            new JsonDocument(self::EXISTING_PLACE_ID, '{}')
        );

        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->updateLocationRequestHandler = new UpdateLocationRequestHandler(
            $this->commandBus,
            $locationRepository
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_given_location_id_does_not_exist(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'dac793c2-4a8c-4744-b593-69420cfbf7bb')
            ->withRouteParameter('locationId', '74e62b6c-9df4-42e4-bcd5-f4c242b4df2e')
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound(
                'Location with id "74e62b6c-9df4-42e4-bcd5-f4c242b4df2e" does not exist.'
            ),
            fn () => $this->updateLocationRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_if_the_given_location_is_a_nil_location(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'dac793c2-4a8c-4744-b593-69420cfbf7bb')
            ->withRouteParameter('locationId', Uuid::NIL)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::attendanceModeNotSupported(
                'Cannot update the location of an offline or mixed event to a nil location. Set the attendanceMode to online instead.'
            ),
            fn () => $this->updateLocationRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_location_command(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'dac793c2-4a8c-4744-b593-69420cfbf7bb')
            ->withRouteParameter('locationId', self::EXISTING_PLACE_ID)
            ->build('PUT');

        $response = $this->updateLocationRequestHandler->handle($request);

        $this->assertEquals(
            [
                new UpdateLocation(
                    'dac793c2-4a8c-4744-b593-69420cfbf7bb',
                    new LocationId(self::EXISTING_PLACE_ID)
                ),
            ],
            $this->commandBus->getRecordedCommands()
        );
        $this->assertInstanceOf(NoContentResponse::class, $response);
    }
}
