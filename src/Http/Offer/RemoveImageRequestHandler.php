<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\RemoveImage as EventRemoveImage;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Media\MediaManagerInterface;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\RemoveImage as PlaceRemoveImage;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RemoveImageRequestHandler implements RequestHandlerInterface
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
        $mediaId = new UUID($routeParameters->getMediaId());

        // Can we be sure that the given $mediaObjectId points to an image and not a different type?
        $image = $this->mediaManager->getImage($mediaId);

        if ($routeParameters->getOfferType()->sameAs(OfferType::event())) {
            $removeImage = new EventRemoveImage(
                $offerId,
                $image
            );
        } else {
            $removeImage = new PlaceRemoveImage(
                $offerId,
                $image
            );
        }

        $this->commandBus->dispatch($removeImage);

        return new NoContentResponse();
    }
}
