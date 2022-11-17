<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Deserializer\Offer\SelectMainImageDenormalizer;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SelectMainImageRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private MediaManagerInterface $mediaManager;

    public function __construct(
        CommandBus $commandBus,
        MediaManagerInterface $mediaManager
    ) {
        $this->commandBus = $commandBus;
        $this->mediaManager = $mediaManager;
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
                    JsonSchemaLocator::EVENT_MAIN_IMAGE_PUT,
                    JsonSchemaLocator::PLACE_MAIN_IMAGE_PUT,
                )
            ),
            new DenormalizingRequestBodyParser(
                new SelectMainImageDenormalizer($this->mediaManager, $offerType, $offerId),
                AbstractSelectMainImage::class
            ),
        );

        /** @var AbstractSelectMainImage $selectMainImage */
        $selectMainImage = $requestBodyParser->parse($request)->getParsedBody();

        $this->commandBus->dispatch($selectMainImage);

        return new NoContentResponse();
    }
}
