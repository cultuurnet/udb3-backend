<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Productions\RejectSuggestedEventPair;
use CultuurNet\UDB3\Event\Productions\SimilarEventPair;
use CultuurNet\UDB3\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class SkipEventsRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private SkipEventsValidator $skipEventsValidator;

    public function __construct(CommandBus $commandBus, SkipEventsValidator $skipEventsValidator)
    {
        $this->commandBus = $commandBus;
        $this->skipEventsValidator = $skipEventsValidator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $data = Json::decodeAssociatively($request->getBody()->getContents());

        $this->skipEventsValidator->validate($data);

        $command = new RejectSuggestedEventPair(
            SimilarEventPair::fromArray($data['eventIds'])
        );

        $this->commandBus->dispatch($command);

        return new Response();
    }
}
