<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
use CultuurNet\UDB3\SavedSearches\Command\UpdateSavedSearchJSONDeserializer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateSavedSearchRequestHandler
{
    private string $userId;

    private CommandBus $commandBus;

    public function __construct(
        $userId,
        $commandBus
    ) {
        $this->userId = $userId;
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $id = $routeParameters->get('id');

        $commandDeserializer = new UpdateSavedSearchJSONDeserializer(
            $this->userId,
            $id
        );

        $command = $commandDeserializer->deserialize($request->getBody()->getContents());

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }
}
