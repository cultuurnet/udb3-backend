<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Productions\MergeProductions;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MergeProductionsRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $fromProductionId = $routeParameters->get('fromProductionId');
        $productionId = $routeParameters->getProductionId();

        $command = new MergeProductions(
            ProductionId::fromNative($fromProductionId),
            ProductionId::fromNative($productionId)
        );

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }
}
