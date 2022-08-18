<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\RenameProduction;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RenameProductionRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private RenameProductionValidator $renameProductionValidator;

    public function __construct(
        CommandBus $commandBus,
        RenameProductionValidator $renameProductionValidator
    ) {
        $this->commandBus = $commandBus;
        $this->renameProductionValidator = $renameProductionValidator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $productionId = $routeParameters->get('productionId');

        $data = Json::decodeAssociatively($request->getBody()->getContents());
        $this->renameProductionValidator->validate($data);

        $command = new RenameProduction(
            ProductionId::fromNative($productionId),
            $data['name']
        );

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }
}
