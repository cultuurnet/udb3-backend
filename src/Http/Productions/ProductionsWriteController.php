<?php

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\UDB3\Event\Productions\AddEventToProduction;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use CultuurNet\UDB3\Event\Productions\MergeProductions;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\RemoveEventFromProduction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionsWriteController
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus;

    /**
     * @var CreateProductionValidator
     */
    private $createProductionValidator;

    public function __construct(
        CommandBusInterface $commandBus,
        CreateProductionValidator $createProductionValidator
    ) {
        $this->commandBus = $commandBus;
        $this->createProductionValidator = $createProductionValidator;
    }

    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->createProductionValidator->validate($data);

        $command = new GroupEventsAsProduction(
            $data['eventIds'],
            $data['name']
        );

        $this->commandBus->dispatch($command);

        return new Response('', 201);
    }

    public function addEventToProduction(
        string $productionId,
        string $eventId
    ): Response {
        $command = new AddEventToProduction(
            $eventId,
            ProductionId::fromNative($productionId)
        );

        $this->commandBus->dispatch($command);

        return new Response('', 200);
    }

    public function removeEventFromProduction(
        string $productionId,
        string $eventId
    ): Response {
        $command = new RemoveEventFromProduction(
            $eventId,
            ProductionId::fromNative($productionId)
        );

        $this->commandBus->dispatch($command);

        return new Response('', 200);
    }

    public function mergeProductions(
        string $productionId,
        string $fromProductionId
    ): Response {
        $command = new MergeProductions(
            ProductionId::fromNative($fromProductionId),
            ProductionId::fromNative($productionId)
        );

        $this->commandBus->dispatch($command);

        return new Response('', 200);
    }
}
