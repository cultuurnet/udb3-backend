<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\CalendarDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Offer\Commands\UpdateCalendar;
use CultuurNet\UDB3\Offer\OfferType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateCalendarRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        $jsonSchema = $offerType->sameAs(OfferType::event()) ? JsonSchemaLocator::EVENT_CALENDAR_PUT : JsonSchemaLocator::PLACE_CALENDAR_PUT;

        $parser = RequestBodyParserFactory::createBaseParser(
            new LegacyUpdateCalendarRequestBodyParser(),
            new UpdateCalendarValidatingRequestBodyParser($jsonSchema),
            new DenormalizingRequestBodyParser(new CalendarDenormalizer(), Calendar::class)
        );

        /** @var Calendar $calendar */
        $calendar = $parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(
            new UpdateCalendar($offerId, $calendar)
        );

        return new NoContentResponse();
    }
}
