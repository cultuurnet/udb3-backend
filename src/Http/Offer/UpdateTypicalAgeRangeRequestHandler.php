<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Deserializer\Offer\UpdateTypicalAgeRangeDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Audience\InvalidAgeRangeException;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTypicalAgeRange;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateTypicalAgeRangeRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $offerType = $routeParameters->getOfferType();

        try {
            $requestBodyParser = RequestBodyParserFactory::createBaseParser(
                new JsonSchemaValidatingRequestBodyParser(
                    JsonSchemaLocator::getSchemaFileByOfferType(
                        $offerType,
                        JsonSchemaLocator::EVENT_TYPICAL_AGE_RANGE_PUT,
                        JsonSchemaLocator::PLACE_TYPICAL_AGE_RANGE_PUT,
                    )
                ),
                new DenormalizingRequestBodyParser(
                    new UpdateTypicalAgeRangeDenormalizer($offerType, $offerId),
                    AbstractUpdateTypicalAgeRange::class
                )
            );

            /** @var AbstractUpdateTypicalAgeRange $updateTypicalAgeRange */
            $updateTypicalAgeRange = $requestBodyParser->parse($request)->getParsedBody();
        } catch (InvalidAgeRangeException $exception) {
            throw ApiProblem::bodyInvalidData(
                new SchemaError('/typicalAgeRange', $exception->getMessage())
            );
        }

        $this->commandBus->dispatch($updateTypicalAgeRange);

        return new NoContentResponse();
    }
}
