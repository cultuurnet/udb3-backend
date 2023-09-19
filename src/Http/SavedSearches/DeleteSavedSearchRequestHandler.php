<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteSavedSearchRequestHandler implements RequestHandlerInterface
{
    private string $userId;

    private CommandBus $commandBus;

    public function __construct(
        string $userId,
        CommandBus $commandBus
    ) {
        $this->userId = $userId;
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $id = $routeParameters->get('id');

        $command = new UnsubscribeFromSavedSearch(
            $this->userId,
            $id
        );

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }
}
