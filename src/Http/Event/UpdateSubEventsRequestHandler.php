<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateSubEvents;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\SubEventUpdateDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateSubEventsRequestHandler implements RequestHandler
{
    private CommandBus $commandBus;

    private RequestBodyParser $updateSubEventsParser;

    private SubEventUpdateDenormalizer $subEventUpdateDenormalizer;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;

        $this->updateSubEventsParser = RequestBodyParserFactory::createBaseParser(
            JsonSchemaValidatingRequestBodyParser::fromFile(JsonSchemaLocator::EVENT_SUB_EVENT_PATCH)
        );

        $this->subEventUpdateDenormalizer = new SubEventUpdateDenormalizer();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->get('eventId');

        $updates = $this->updateSubEventsParser->parse($request)->getParsedBody();

        $updateSubEvents = [];
        foreach ($updates as $update) {
            $updateSubEvents[] = \CultuurNet\UDB3\Event\ValueObjects\SubEventUpdate::fromUdb3ModelsSubEventUpdate(
                $this->subEventUpdateDenormalizer->denormalize($update, SubEventUpdate::class)
            );
        }

        $this->commandBus->dispatch(new UpdateSubEvents($eventId, ...$updateSubEvents));

        return new NoContentResponse();
    }
}
