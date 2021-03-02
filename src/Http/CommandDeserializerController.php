<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
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

    public function __construct(
        DeserializerInterface $commandDeserializer,
        CommandBus $commandBus
    ) {
        $this->deserializer = $commandDeserializer;
        $this->setCommandBus($commandBus);
    }

    /**
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
