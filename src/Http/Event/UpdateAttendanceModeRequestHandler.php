<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateAttendanceMode;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\Serializer\AttendanceModeWithLocation;
use CultuurNet\UDB3\Event\Serializer\AttendanceModeWithLocationDenormalizer;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Online\AttendanceMode;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateAttendanceModeRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private EventRelationsRepository $eventRelationsRepository;

    public function __construct(CommandBus $commandBus, EventRelationsRepository $eventRelationsRepository)
    {
        $this->commandBus = $commandBus;
        $this->eventRelationsRepository = $eventRelationsRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT_ATTENDANCE_MODE_PUT),
            new DenormalizingRequestBodyParser(
                new AttendanceModeWithLocationDenormalizer(),
                AttendanceModeWithLocation::class
            )
        );

        /** @var AttendanceModeWithLocation $attendanceModeWithLocation */
        $attendanceModeWithLocation = $parser->parse($request)->getParsedBody();

        $this->guardLocation($eventId, $this->eventRelationsRepository, $attendanceModeWithLocation);

        $commands = [
            new UpdateAttendanceMode(
                $eventId,
                $attendanceModeWithLocation->getAttendanceMode()
            ),
        ];

        if ($attendanceModeWithLocation->getLocationId()) {
            $commands[] = new UpdateLocation(
                $eventId,
                $attendanceModeWithLocation->getLocationId()
            );
        }

        foreach ($commands as $command) {
            $this->commandBus->dispatch($command);
        }

        return new NoContentResponse();
    }

    private function guardLocation(
        string $eventId,
        EventRelationsRepository $eventRelationsRepository,
        AttendanceModeWithLocation $attendanceModeWithLocation
    ): void {
        if ($attendanceModeWithLocation->getLocationId() === null &&
            !$attendanceModeWithLocation->getAttendanceMode()->sameAs(AttendanceMode::online())) {
            $location = $eventRelationsRepository->getPlaceOfEvent($eventId);

            if ($location === null || $location === Uuid::NIL) {
                throw ApiProblem::bodyInvalidData(
                    new SchemaError(
                        '/',
                        'A location is required when changing an online event to mixed or offline'
                    )
                );
            }
        }
    }
}
