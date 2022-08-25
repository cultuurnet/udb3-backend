<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\SavedSearches\Command\UnsubscribeFromSavedSearch;
use CultuurNet\UDB3\StringLiteral;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteSavedSearchesRequestHandler implements RequestHandlerInterface
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
            new StringLiteral($this->userId),
            new StringLiteral($id)
        );

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }
}
