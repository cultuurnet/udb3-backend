<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\DeleteEvent;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOffer;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\DeletePlace;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

final class DeleteRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerType = $routeParameters->getOfferType();
        $offerId = $routeParameters->getOfferId();

        $command = $this->createDeleteCommand($offerType, $offerId);

        $this->commandBus->dispatch($command);

        return new NoContentResponse();
    }

    private function createDeleteCommand(OfferType $offerType, string $offerId): AbstractDeleteOffer
    {
        if ($offerType->sameValueAs(OfferType::EVENT())) {
            return new DeleteEvent($offerId);
        }
        if ($offerType->sameValueAs(OfferType::PLACE())) {
            return new DeletePlace($offerId);
        }
        throw new RuntimeException('No AbstractDeleteOffer implementation found for unknown offer type ' . $offerType->toNative());
    }
}
