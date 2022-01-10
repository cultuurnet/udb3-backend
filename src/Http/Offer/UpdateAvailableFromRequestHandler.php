<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Deserializer\Offer\AvailableFromDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\UpdateAvailableFrom;
use CultuurNet\UDB3\Offer\OfferType;
use DateTimeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateAvailableFromRequestHandler implements RequestHandlerInterface
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

        $jsonSchema = $offerType->sameAs(OfferType::event()) ?
            JsonSchemaLocator::EVENT_AVAILABLE_FROM_PUT : JsonSchemaLocator::PLACE_AVAILABLE_FROM_PUT;

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser($jsonSchema),
            new DenormalizingRequestBodyParser(new AvailableFromDenormalizer(), DateTimeInterface::class)
        );

        /** @var DateTimeInterface $availableFrom */
        $availableFrom = $parser->parse($request)->getParsedBody();
        $this->commandBus->dispatch(new UpdateAvailableFrom($offerId, $availableFrom));

        return new NoContentResponse();
    }
}
