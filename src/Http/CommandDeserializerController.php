<?php

namespace CultuurNet\UDB3\Http;

use Broadway\CommandHandling\CommandBusInterface;
use CultuurNet\Deserializer\DeserializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Creates a command by deserializing the body of a request using the injected
 * deserializer, and dispatches it to the injected command bus.
 */
class CommandDeserializerController
{
    use CommandDeserializerControllerTrait;

    /**
     * @var DeserializerInterface
     */
    private $deserializer;

    /**
     * @param DeserializerInterface $commandDeserializer
     * @param CommandBusInterface $commandBus
     */
    public function __construct(
        DeserializerInterface $commandDeserializer,
        CommandBusInterface $commandBus
    ) {
        $this->deserializer = $commandDeserializer;
        $this->setCommandBus($commandBus);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request)
    {
        return $this->handleRequestWithDeserializer(
            $request,
            $this->deserializer
        );
    }
}
