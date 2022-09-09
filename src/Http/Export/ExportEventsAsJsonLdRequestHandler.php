<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Export;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\EventExport\Command\ExportEventsAsJsonLDJSONDeserializer;
use CultuurNet\UDB3\Http\AsyncDispatchTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\StringLiteral;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ExportEventsAsJsonLdRequestHandler implements RequestHandlerInterface
{
    use AsyncDispatchTrait;

    private CommandBus $commandBus;

    private DeserializerInterface $deserializer;

    public function __construct(
        CommandBus $commandBus
    ) {
        $this->commandBus = $commandBus;
        $this->deserializer = new ExportEventsAsJsonLDJSONDeserializer();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $command = $this->deserializer->deserialize(
            new StringLiteral($request->getBody()->getContents())
        );
        $commandId = $this->dispatchAsyncCommand($this->commandBus, $command);

        return new JsonResponse(['commandId' => $commandId]);
    }
}
