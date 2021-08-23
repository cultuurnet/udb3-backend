<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarForPlaceDataValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONDeserializer;
use CultuurNet\UDB3\Http\Deserializer\Calendar\CalendarJSONParser;
use CultuurNet\UDB3\Http\Request\RequestHandler;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ValueObjects\StringLiteral\StringLiteral;

final class UpdateCalendarRequestHandler implements RequestHandler
{
    private CommandBus $commandBus;
    private CalendarJSONDeserializer $calendarJsonDeserializer;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
        $this->calendarJsonDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            new CalendarForPlaceDataValidator()
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->get('placeId');

        $calendar = $this->calendarJsonDeserializer->deserialize(
            new StringLiteral((string) $request->getBody())
        );

        $this->commandBus->dispatch(new UpdateCalendar($offerId, $calendar));

        return new NoContentResponse();
    }
}
