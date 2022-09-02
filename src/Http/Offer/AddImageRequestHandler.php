<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\AddImage as EventAddImage;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\AddImage as PlaceAddImage;
use Fig\Http\Message\StatusCodeInterface;
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
        $bodyContent = Json::decode($request->getBody()->getContents());

        if (empty($bodyContent->mediaObjectId)) {
            return new JsonResponse(
                ['error' => 'media object id required'],
                StatusCodeInterface::STATUS_BAD_REQUEST
            );
        }

        // @todo Validate that this id exists and is in fact an image and not a different type of media object
        $imageId = new UUID($bodyContent->mediaObjectId);

        if ($routeParameters->getOfferType()->sameAs(OfferType::event())) {
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
