<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractUpdateImage;
use CultuurNet\UDB3\Offer\Serializers\UpdateImageDenormalizer;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateImageRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(
        CommandBus $commandBus
    ) {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): NoContentResponse
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        try {
            $mediaId = new Uuid($routeParameters->getMediaId());
        } catch (\InvalidArgumentException $exception) {
            throw ApiProblem::imageNotFound($routeParameters->getMediaId());
        }

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_IMAGE_PUT,
                    JsonSchemaLocator::PLACE_IMAGE_PUT,
                )
            ),
            new DenormalizingRequestBodyParser(
                new UpdateImageDenormalizer($offerType, $offerId, $mediaId),
                AbstractUpdateImage::class,
            ),
        );

        $request = $requestBodyParser->parse($request);

        /** @var AbstractUpdateImage $updateImage */
        $updateImage = $request->getParsedBody();

        $this->commandBus->dispatch($updateImage);

        return new NoContentResponse();
    }
}
