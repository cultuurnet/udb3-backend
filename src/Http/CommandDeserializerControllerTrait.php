<?php

namespace CultuurNet\UDB3\Http;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;

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
        $commandId = $this->commandBus->dispatch($command);

        return JsonResponse::create(
            ['commandId' => $commandId]
        );
    }
}
