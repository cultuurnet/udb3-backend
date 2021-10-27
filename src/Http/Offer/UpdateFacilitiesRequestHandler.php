<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\EventFacilityResolver;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateFacilities;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\PlaceFacilityResolver;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use ValueObjects\StringLiteral\StringLiteral;

class UpdateFacilitiesRequestHandler implements RequestHandlerInterface
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

        $parser = RequestBodyParserFactory::createBaseParser(
            new UpdateFacilitiesBackwardCompatibilityRequestBodyParser(),
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_FACILITIES_PUT,
                    JsonSchemaLocator::PLACE_FACILITIES_PUT
                )
            )
        );

        /** @var array $facilityIds */
        $facilityIds = $parser->parse($request)->getParsedBody();

        $this->commandBus->dispatch(new UpdateFacilities($offerId, $facilityIds));

        return new NoContentResponse();
    }
}
