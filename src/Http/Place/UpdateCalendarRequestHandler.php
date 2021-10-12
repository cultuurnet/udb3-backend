<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Calendar as LegacyCalendar;
use CultuurNet\UDB3\Http\Offer\UpdateCalendarBackwardCompatibilityRequestBodyParser;
use CultuurNet\UDB3\Http\Offer\UpdateCalendarValidationRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateCalendarRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private RequestBodyParser $parser;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
        $this->parser = RequestBodyParserFactory::createBaseParser(
            new UpdateCalendarBackwardCompatibilityRequestBodyParser(),
            new UpdateCalendarValidationRequestBodyParser(JsonSchemaLocator::PLACE_CALENDAR_PUT),
            new DenormalizingRequestBodyParser(new CalendarDenormalizer(), Calendar::class)
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getPlaceId();

        /** @var Calendar $calendar */
        $calendar = $this->parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(
            new UpdateCalendar($offerId, LegacyCalendar::fromUdb3ModelCalendar($calendar))
        );

        return new NoContentResponse();
    }
}
