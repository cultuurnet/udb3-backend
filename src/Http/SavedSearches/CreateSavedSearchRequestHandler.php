<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\SavedSearches;

use Broadway\CommandHandling\CommandBus;
use Broadway\UuidGenerator\UuidGeneratorInterface;
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

    private UuidGeneratorInterface $uuidGenerator;

    public function __construct(
        string $userId,
        CommandBus $commandBus,
        UuidGeneratorInterface $uuidGenerator
    ) {
        $this->userId = $userId;
        $this->commandBus = $commandBus;
        $this->uuidGenerator = $uuidGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $this->uuidGenerator->generate();

        $commandDeserializer = new SubscribeToSavedSearchJSONDeserializer(
            $id,
            $this->userId
        );

        $command = $commandDeserializer->deserialize($request->getBody()->getContents());

        $this->commandBus->dispatch($command);

        return new JsonResponse(['id' => $id], StatusCodeInterface::STATUS_CREATED);
    }
}
