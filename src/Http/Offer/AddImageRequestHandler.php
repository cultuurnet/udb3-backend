<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\AddImage as EventAddImage;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\AddImage as PlaceAddImage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AddImageRequestHandler implements RequestHandlerInterface
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

        $bodyContent = Json::decode($request->getBody()->getContents());

        if (empty($bodyContent->mediaObjectId)) {
            throw ApiProblem::bodyInvalidDataWithDetail('media object id required');
        }

        // @todo Validate that this id exists and is in fact an image and not a different type of media object
        $imageId = new UUID($bodyContent->mediaObjectId);

        if ($offerType->sameAs(OfferType::event())) {
            $addImage = new EventAddImage(
                $offerId,
                $imageId
            );
        } else {
            $addImage = new PlaceAddImage(
                $offerId,
                $imageId
            );
        }

        $this->commandBus->dispatch($addImage);

        return new NoContentResponse();
    }
}
