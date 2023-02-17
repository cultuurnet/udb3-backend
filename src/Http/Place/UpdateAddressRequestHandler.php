<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Deserializer\Place\UpdateAddressDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateAddressRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $placeId = $routeParameters->getPlaceId();
        $language = $routeParameters->getLanguage();


        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::PLACE_ADDRESS_PUT),
            new DenormalizingRequestBodyParser(
                new UpdateAddressDenormalizer($placeId, $language),
                UpdateAddress::class
            )
        );

        /** @var UpdateAddress $updateAddress */
        $updateAddress = $requestBodyParser->parse($request)->getParsedBody();

        $this->commandBus->dispatch($updateAddress);

        return new NoContentResponse();
    }
}
