<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\DeleteTypicalAgeRange as EventDeleteTypicalAgeRange;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange as PlaceDeleteTypicalAgeRange;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteTypicalAgeRangeRequestHandler implements RequestHandlerInterface
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

        if ($routeParameters->getOfferType()->sameAs(OfferType::event())) {
            $deleteTypicalAgeRange = new EventDeleteTypicalAgeRange($offerId);
        } else {
            $deleteTypicalAgeRange = new PlaceDeleteTypicalAgeRange($offerId);
        }

        $this->commandBus->dispatch($deleteTypicalAgeRange);

        return new NoContentResponse();
    }
}
