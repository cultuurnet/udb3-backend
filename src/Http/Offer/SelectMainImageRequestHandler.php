<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\SelectMainImage as EventSelectMainImage;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Media\MediaObjectNotFoundException;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\SelectMainImage as PlaceSelectMainImage;
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

        $bodyContent = Json::decode($request->getBody()->getContents());

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_MAIN_IMAGE_PUT,
                    JsonSchemaLocator::PLACE_MAIN_IMAGE_PUT,
                )
            ),
        );

        $requestBodyParser->parse($request);

        $mediaObjectId = new UUID($bodyContent->mediaObjectId);

        // Can we be sure that the given $mediaObjectId points to an image and not a different type?
        try {
            $image = $this->mediaManager->getImage($mediaObjectId);
        } catch (MediaObjectNotFoundException $exception) {
            throw ApiProblem::imageNotFound($mediaObjectId->toString());
        }

        if ($offerType->sameAs(OfferType::event())) {
            $selectMainImage = new EventSelectMainImage(
                $offerId,
                $image
            );
        } else {
            $selectMainImage = new PlaceSelectMainImage(
                $offerId,
                $image
            );
        }

        $this->commandBus->dispatch($selectMainImage);

        return new NoContentResponse();
    }
}
