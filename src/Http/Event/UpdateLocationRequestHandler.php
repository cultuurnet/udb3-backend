<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\AttendanceModeNotSupported;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Docs\Stoplight;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateLocationRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private DocumentRepository $locationDocumentRepository;

    public function __construct(CommandBus $commandBus, DocumentRepository $locationDocumentRepository)
    {
        $this->commandBus = $commandBus;
        $this->locationDocumentRepository = $locationDocumentRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();
        $locationId = $routeParameters->get('locationId');

        if ((new LocationId($locationId))->isNilLocation()) {
            throw new AttendanceModeNotSupported(
                'Cannot update the location of an offline or mixed event to a nil location. Set the attendanceMode to online instead.'
            );
        }

        try {
            // The UpdateLocation handler does not validate the location id, so we should do it here for now to prevent
            // non-existing places being linked.
            $this->locationDocumentRepository->fetch($locationId);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::urlNotFound('Location with id "' . $locationId . '" does not exist.');
        }

        $this->commandBus->dispatch(new UpdateLocation($eventId, new LocationId($locationId)));

        return new NoContentResponse();
    }
}
