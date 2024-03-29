<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\RemoveEventFromProduction;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RemoveEventFromProductionRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();
        $productionId = $routeParameters->getProductionId();

        $command = new RemoveEventFromProduction(
            $eventId,
            ProductionId::fromNative($productionId)
        );

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }
}
