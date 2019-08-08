<?php

namespace CultuurNet\UDB3\Http;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DeserializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use ValueObjects\StringLiteral\StringLiteral;

trait CommandDeserializerControllerTrait
{
    /**
     * @var CommandBusInterface
     */
    private $commandBus = null;

    /**
     * @param CommandBusInterface $commandBus
     */
    private function setCommandBus(CommandBusInterface $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * @param Request $request
     * @param DeserializerInterface $deserializer
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
     * @param mixed $command
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
