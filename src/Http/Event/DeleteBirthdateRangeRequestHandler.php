<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\DeleteBirthdateRange;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteBirthdateRangeRequestHandler implements RequestHandlerInterface
{
    public function __construct(private readonly CommandBus $commandBus)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $this->commandBus->dispatch(new DeleteBirthdateRange($eventId));

        return new NoContentResponse();
    }
}
