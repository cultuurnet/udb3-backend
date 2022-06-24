<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\CommandHandling\AsyncCommand;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use CultuurNet\UDB3\StringLiteral;

trait CommandDeserializerControllerTrait
{
    /**
     * @var CommandBus
     */
    private $commandBus = null;


    private function setCommandBus(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @return JsonResponse
     */
    private function handleRequestWithDeserializer(
        Request $request,
        DeserializerInterface $deserializer
    ) {
        $command = $deserializer->deserialize(
            new StringLiteral($request->getContent())
        );

        return $this->handleCommand($command);
    }

    /**
     * @return JsonResponse
     */
    private function handleCommand($command)
    {
        $this->commandBus->dispatch($command);

        $commandId = '00000000-0000-0000-0000-000000000000';
        if ($command instanceof AsyncCommand) {
            $commandId = $command->getAsyncCommandId() ?? $commandId;
        }

        return JsonResponse::create(
            ['commandId' => $commandId]
        );
    }
}
