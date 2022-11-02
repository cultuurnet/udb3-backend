<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Offer\Serializers\AddImageDenormalizer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AddImageRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private Repository $mediaRepository;

    public function __construct(CommandBus $commandBus, Repository $mediaRepository)
    {
        $this->commandBus = $commandBus;
        $this->mediaRepository = $mediaRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $offerType = $routeParameters->getOfferType();

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_IMAGE_POST,
                    JsonSchemaLocator::PLACE_IMAGE_POST,
                )
            ),
            new AddMediaObjectPropertiesRequestBodyParser($this->mediaRepository),
            new DenormalizingRequestBodyParser(
                new AddImageDenormalizer($offerType, $offerId),
                AbstractAddImage::class
            ),
        );

        $request = $requestBodyParser->parse($request);

        /** @var AbstractAddImage $addImage */
        $addImage = $requestBodyParser->parse($request)->getParsedBody();

        $this->commandBus->dispatch($addImage);

        return new NoContentResponse();
    }
}
