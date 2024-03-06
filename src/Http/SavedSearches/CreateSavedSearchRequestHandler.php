<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\SavedSearches\Command\SubscribeToSavedSearchJSONDeserializer;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CreateSavedSearchRequestHandler implements RequestHandlerInterface
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
            $this->userId
        );

        

        $command = $commandDeserializer->deserialize($request->getBody()->getContents());

        $this->commandBus->dispatch($command);

        return new JsonResponse(['id' => $command->], StatusCodeInterface::STATUS_CREATED);
    }
}
