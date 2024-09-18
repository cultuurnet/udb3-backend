<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Deserializer\Offer\UpdateContactPointDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\ContactPointDenormalizer;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateContactPoint;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateContactPointRequestHandler implements RequestHandlerInterface
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

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new LegacyContactPointRequestBodyParser(),
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_CONTACT_POINT_PUT,
                    JsonSchemaLocator::PLACE_CONTACT_POINT_PUT,
                )
            ),
            new DenormalizingRequestBodyParser(
                new UpdateContactPointDenormalizer(
                    $offerType,
                    $offerId,
                    new ContactPointDenormalizer()
                ),
                AbstractUpdateContactPoint::class,
            ),
        );

        /** @var AbstractUpdateContactPoint $updateContactPoint */
        $updateContactPoint = $requestBodyParser->parse($request)->getParsedBody();

        $this->commandBus->dispatch($updateContactPoint);

        return new NoContentResponse();
    }
}
