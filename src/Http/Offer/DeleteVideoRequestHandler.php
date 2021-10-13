<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\Video\DeleteVideo;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferJsonDocumentReadRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteVideoRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private OfferJsonDocumentReadRepository $offerJsonDocumentReadRepository;

    public function __construct(
        CommandBus $commandBus,
        OfferJsonDocumentReadRepository $offerJsonDocumentReadRepository
    ) {
        $this->commandBus = $commandBus;
        $this->offerJsonDocumentReadRepository = $offerJsonDocumentReadRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();
        $videoId = $routeParameters->get('videoId');

        // Fetch the event/place to validate the existence, if not an ApiProblem is thrown
        $this->offerJsonDocumentReadRepository->fetch($offerType, $offerId);

        $this->commandBus->dispatch(new DeleteVideo($offerId, $videoId));

        return new NoContentResponse();
    }
}
