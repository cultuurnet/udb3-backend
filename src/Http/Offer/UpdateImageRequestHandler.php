<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateImage as EventUpdateImage;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateImage as PlaceUpdateImage;
use CultuurNet\UDB3\StringLiteral;
use Psr\Http\Message\ResponseInterface;
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

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        $bodyContent = Json::decode($request->getBody()->getContents());

        try {
            $mediaId = new UUID($routeParameters->getMediaId());
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
            )
        );

        $requestBodyParser->parse($request);

        $description = new StringLiteral($bodyContent->description);
        $copyrightHolder = new CopyrightHolder($bodyContent->copyrightHolder);

        if ($offerType->sameAs(OfferType::event())) {
            $updateImage = new EventUpdateImage(
                $offerId,
                $mediaId,
                $description,
                $copyrightHolder
            );
        } else {
            $updateImage = new PlaceUpdateImage(
                $offerId,
                $mediaId,
                $description,
                $copyrightHolder
            );
        }

        $this->commandBus->dispatch($updateImage);

        return new NoContentResponse();
    }
}
