<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Export;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsOOXMLJSONDeserializer;
use CultuurNet\UDB3\Http\AsyncDispatchTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ExportEventsAsOoXmlRequestHandler implements RequestHandlerInterface
{
    use AsyncDispatchTrait;

    private CommandBus $commandBus;

    private DeserializerInterface $deserializer;

    public function __construct(
        CommandBus $commandBus
    ) {
        $this->commandBus = $commandBus;
        $this->deserializer = new ExportEventsAsOOXMLJSONDeserializer();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->deserializer->deserialize($request->getBody()->getContents());
        $commandId = $this->dispatchAsyncCommand($this->commandBus, $command);

        return new JsonResponse(['commandId' => $commandId]);
    }
}
