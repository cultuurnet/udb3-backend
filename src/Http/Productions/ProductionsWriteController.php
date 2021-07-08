<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Event\Productions\AddEventToProduction;
use CultuurNet\UDB3\Event\Productions\GroupEventsAsProduction;
use CultuurNet\UDB3\Event\Productions\MergeProductions;
use CultuurNet\UDB3\Event\Productions\ProductionId;
use CultuurNet\UDB3\Event\Productions\RemoveEventFromProduction;
use CultuurNet\UDB3\Event\Productions\RejectSuggestedEventPair;
use CultuurNet\UDB3\Event\Productions\RenameProduction;
use CultuurNet\UDB3\Event\Productions\SimilarEventPair;
use CultuurNet\UDB3\HttpFoundation\Response\JsonLdResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionsWriteController
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var CreateProductionValidator
     */
    private $createProductionValidator;

    /**
     * @var SkipEventsValidator
     */
    private $skipEventsValidator;

    public function __construct(
        CommandBus $commandBus,
        CreateProductionValidator $createProductionValidator,
        SkipEventsValidator $skipEventsValidator
    ) {
        $this->commandBus = $commandBus;
        $this->createProductionValidator = $createProductionValidator;
        $this->skipEventsValidator = $skipEventsValidator;
    }

    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $this->createProductionValidator->validate($data);

        $command = GroupEventsAsProduction::withProductionName(
            $data['eventIds'],
            $data['name']
        );

        $this->commandBus->dispatch($command);

        return new JsonLdResponse(['productionId' => $command->getItemId()], 201);
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

        return new Response('', 204);
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

        return new Response('', 204);
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

        return new Response('', 204);
    }

    public function renameProduction(string $productionId, string $productionName): Response
    {
        if (empty($productionName)) {
            throw new DataValidationException('The new production name is required');
        }

        $renameCommand = new RenameProduction(ProductionId::fromNative($productionId), $productionName);

        $this->commandBus->dispatch($renameCommand);

        return new Response('', 204);
    }

    public function skipEvents(Request $request)
    {
        $data = (array)json_decode($request->getContent(), true);

        $this->skipEventsValidator->validate($data);

        $this->commandBus->dispatch(
            new RejectSuggestedEventPair(
                SimilarEventPair::fromArray($data['eventIds'])
            )
        );

        return new Response('', 200);
    }
}
