<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\CopyEvent;
use CultuurNet\UDB3\Http\Offer\LegacyUpdateCalendarRequestBodyParser;
use CultuurNet\UDB3\Http\Offer\UpdateCalendarValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UuidFactory\UuidFactoryInterface;

final class CopyEventRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private UuidFactoryInterface $uuidFactory;
    private IriGeneratorInterface $iriGenerator;

    public function __construct(
        CommandBus $commandBus,
        UuidFactoryInterface $uuidFactory,
        IriGeneratorInterface $iriGenerator
    ) {
        $this->commandBus = $commandBus;
        $this->uuidFactory = $uuidFactory;
        $this->iriGenerator = $iriGenerator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $parser = RequestBodyParserFactory::createBaseParser(
            new LegacyUpdateCalendarRequestBodyParser(),
            new UpdateCalendarValidatingRequestBodyParser(JsonSchemaLocator::EVENT_CALENDAR_PUT),
            new DenormalizingRequestBodyParser(new CalendarDenormalizer(), Calendar::class)
        );

        /** @var Calendar $calendar */
        $calendar = $parser->parse($request)->getParsedBody();

        $newEventId = $this->uuidFactory->uuid4()->toString();

        $this->commandBus->dispatch(new CopyEvent($eventId, $newEventId, $calendar));

        return new JsonResponse(
            [
                'eventId' => $newEventId,
                'url' => $this->iriGenerator->iri($newEventId),
            ],
            StatusCodeInterface::STATUS_CREATED
        );
    }
}
