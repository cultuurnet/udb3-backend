<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
use CultuurNet\UDB3\StringLiteral;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class SaveSavedSearchesRequestHandler implements RequestHandlerInterface
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
        $commandDeserializer = new SubscribeToSavedSearchJSONDeserializer(
            new StringLiteral($this->userId)
        );

        $command = $commandDeserializer->deserialize(
            new StringLiteral($request->getBody()->getContents())
        );

        $this->commandBus->dispatch($command);

        return new Response(StatusCodeInterface::STATUS_CREATED);
    }
}