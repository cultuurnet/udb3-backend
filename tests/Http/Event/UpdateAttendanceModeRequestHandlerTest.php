<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class UpdateAttendanceModeRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;

    private UpdateAttendanceModeRequestHandler $updateAttendanceModeRequestHandler;

    private EventRelationsRepository&MockObject $eventRelationsRepository;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->eventRelationsRepository = $this->createMock(EventRelationsRepository::class);
        $this->updateAttendanceModeRequestHandler = new UpdateAttendanceModeRequestHandler(
            $this->commandBus,
            $this->eventRelationsRepository
        );

        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_for_online_attendance_mode(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'online',
            ])
            ->build('PUT');

        $expectedCommands = [
            new UpdateAttendanceMode(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                AttendanceMode::online()
            ),
            new UpdateLocation(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                new LocationId(Uuid::NIL)
            ),
        ];

        $response = $this->updateAttendanceModeRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals($expectedCommands, $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_for_offline_attendance_mode(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'offline',
                'location' => 'https://io.uitdatabank.be/places/381d9158-5d89-4d84-ae4a-9a3ba0f664fd',
            ])
            ->build('PUT');

        $expectedCommands = [
            new UpdateAttendanceMode(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                AttendanceMode::offline()
            ),
            new UpdateLocation(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                new LocationId('381d9158-5d89-4d84-ae4a-9a3ba0f664fd')
            ),
        ];

        $response = $this->updateAttendanceModeRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals($expectedCommands, $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_for_offline_attendance_without_location(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'offline',
            ])
            ->build('PUT');

        $this->eventRelationsRepository->expects($this->once())
            ->method('getPlaceOfEvent')
            ->with('c269632a-a887-4f21-8455-1631c31e4df5')
            ->willReturn('2bfe3a68-59a4-458a-a409-800e36f3c428');

        $expectedCommands = [
            new UpdateAttendanceMode(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                AttendanceMode::offline()
            ),
        ];

        $response = $this->updateAttendanceModeRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals($expectedCommands, $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_for_mixed_attendance_mode(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'mixed',
                'location' => 'https://io.uitdatabank.be/places/381d9158-5d89-4d84-ae4a-9a3ba0f664fd',
            ])
            ->build('PUT');

        $expectedCommands = [
            new UpdateAttendanceMode(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                AttendanceMode::mixed()
            ),
            new UpdateLocation(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                new LocationId('381d9158-5d89-4d84-ae4a-9a3ba0f664fd')
            ),
        ];

        $response = $this->updateAttendanceModeRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals($expectedCommands, $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_for_mixed_attendance_mode_without_location(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'mixed',
            ])
            ->build('PUT');

        $this->eventRelationsRepository->expects($this->once())
            ->method('getPlaceOfEvent')
            ->with('c269632a-a887-4f21-8455-1631c31e4df5')
            ->willReturn('2bfe3a68-59a4-458a-a409-800e36f3c428');

        $expectedCommands = [
            new UpdateAttendanceMode(
                'c269632a-a887-4f21-8455-1631c31e4df5',
                AttendanceMode::mixed()
            ),
        ];

        $response = $this->updateAttendanceModeRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals($expectedCommands, $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throws_if_online_attendance_mode_has_location(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'online',
                'location' => 'https://io.uitdatabank.be/places/00000000-0000-0000-0000-000000000000',
            ])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError('/', 'Additional object properties are not allowed: location')
            ),
            fn () => $this->updateAttendanceModeRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_offline_attendance_mode_has_missing_location(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'offline',
            ])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/',
                    'A location is required when changing an online event to mixed or offline'
                )
            ),
            fn () => $this->updateAttendanceModeRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_offline_attendance_mode_has_nil_location(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'offline',
            ])
            ->build('PUT');

        $this->eventRelationsRepository->expects($this->once())
            ->method('getPlaceOfEvent')
            ->with('c269632a-a887-4f21-8455-1631c31e4df5')
            ->willReturn(Uuid::NIL);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/',
                    'A location is required when changing an online event to mixed or offline'
                )
            ),
            fn () => $this->updateAttendanceModeRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_mixed_attendance_mode_has_missing_location(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'mixed',
            ])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/',
                    'A location is required when changing an online event to mixed or offline'
                )
            ),
            fn () => $this->updateAttendanceModeRequestHandler->handle($request)
        );
    }

    /**
     * @test
     */
    public function it_throws_if_mixed_attendance_mode_has_nil_location(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray([
                'attendanceMode' => 'mixed',
            ])
            ->build('PUT');

        $this->eventRelationsRepository->expects($this->once())
            ->method('getPlaceOfEvent')
            ->with('c269632a-a887-4f21-8455-1631c31e4df5')
            ->willReturn(Uuid::NIL);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(
                new SchemaError(
                    '/',
                    'A location is required when changing an online event to mixed or offline'
                )
            ),
            fn () => $this->updateAttendanceModeRequestHandler->handle($request)
        );
    }
}
