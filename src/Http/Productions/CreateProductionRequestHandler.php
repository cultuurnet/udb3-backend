<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CreateProductionRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private CreateProductionValidator $createProductionValidator;

    public function __construct(CommandBus $commandBus, CreateProductionValidator $createProductionValidator)
    {
        $this->commandBus = $commandBus;
        $this->createProductionValidator = $createProductionValidator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = Json::decodeAssociatively($request->getBody()->getContents());

        $this->createProductionValidator->validate($data);

        $command = GroupEventsAsProduction::withProductionName(
            $data['eventIds'],
            $data['name']
        );

        $this->commandBus->dispatch($command);

        return new JsonLdResponse(['productionId' => $command->getItemId()], 201);
    }
}
