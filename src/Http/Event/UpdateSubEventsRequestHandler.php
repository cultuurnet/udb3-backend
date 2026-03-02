<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateSubEvents;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\SubEventUpdatesDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdates;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateSubEventsRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private RequestBodyParser $updateSubEventsParser;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;

        $this->updateSubEventsParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT_SUB_EVENT_PATCH),
            new DateRangeValidatingRequestBodyParser(),
            new DenormalizingRequestBodyParser(new SubEventUpdatesDenormalizer(), SubEventUpdates::class)
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        /** @var SubEventUpdates $updates */
        $updates = $this->updateSubEventsParser->parse($request)->getParsedBody();

        try {
            $this->commandBus->dispatch(new UpdateSubEvents($eventId, ...$updates));
        } catch (InvalidArgumentException $exception) {
            throw ApiProblem::bodyInvalidDataWithDetail($exception->getMessage());
        }

        return new NoContentResponse();
    }
}
