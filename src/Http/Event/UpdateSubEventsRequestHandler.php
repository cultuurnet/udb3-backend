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
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\SubEventUpdatesDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdates;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UpdateSubEventsRequestHandler implements RequestHandler
{
    private CommandBus $commandBus;

    private RequestBodyParser $updateSubEventsParser;

    private SubEventUpdatesDenormalizer $subEventUpdatesDenormalizer;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;

        $this->updateSubEventsParser = RequestBodyParserFactory::createBaseParser(
            JsonSchemaValidatingRequestBodyParser::fromFile(JsonSchemaLocator::EVENT_SUB_EVENT_PATCH)
        );

        $this->subEventUpdatesDenormalizer = new SubEventUpdatesDenormalizer();
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->get('eventId');

        $data = $this->updateSubEventsParser->parse($request)->getParsedBody();

        $updates = array_map(
            fn (SubEventUpdate $update) => \CultuurNet\UDB3\Event\ValueObjects\SubEventUpdate::fromUdb3ModelsSubEventUpdate($update),
            $this->subEventUpdatesDenormalizer->denormalize($data, SubEventUpdates::class)->toArray()
        );

        $this->commandBus->dispatch(new UpdateSubEvents($eventId, ...$updates));

        return new NoContentResponse();
    }
}
