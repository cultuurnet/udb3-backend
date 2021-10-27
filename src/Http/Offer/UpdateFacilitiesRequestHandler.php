<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateFacilities as UpdateEventFacilities;
use CultuurNet\UDB3\Event\EventFacilityResolver;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateFacilities;
use CultuurNet\UDB3\Offer\OfferFacilityResolverInterface;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateFacilities as UpdatePlaceFacilities;
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

        /** @var array $data */
        $facilityIds = $parser->parse($request)->getParsedBody();
        $facilityResolver = $this->getFacilityResolver($offerType);

        $facilities = array_map(
            static function (string $facilityId) use ($facilityResolver, $offerType) {
                try {
                    return $facilityResolver->byId(new StringLiteral($facilityId));
                } catch (Exception $e) {
                    throw ApiProblem::bodyInvalidDataWithDetail(
                        sprintf(
                            'Facility id "%s" is invalid or not applicable to %s.',
                            $facilityId,
                            $offerType->toNative()
                        )
                    );
                }
            },
            $facilityIds
        );

        $this->commandBus->dispatch(
            $this->createCommand($offerType, $offerId, $facilities)
        );

        return new NoContentResponse();
    }

    private function getFacilityResolver(OfferType $offerType): OfferFacilityResolverInterface
    {
        if ($offerType->sameValueAs(OfferType::EVENT())) {
            return new EventFacilityResolver();
        }
        if ($offerType->sameValueAs(OfferType::PLACE())) {
            return new PlaceFacilityResolver();
        }
        throw new RuntimeException('No OfferFacilityResolverInterface found for unknown type ' . $offerType->toNative());
    }

    private function createCommand(OfferType $offerType, string $offerId, array $facilities): AbstractUpdateFacilities
    {
        if ($offerType->sameValueAs(OfferType::EVENT())) {
            return new UpdateEventFacilities($offerId, $facilities);
        }
        if ($offerType->sameValueAs(OfferType::PLACE())) {
            return new UpdatePlaceFacilities($offerId, $facilities);
        }
        throw new RuntimeException('No AbstractUpdateFacilities command found for unknown type ' . $offerType->toNative());
    }
}
