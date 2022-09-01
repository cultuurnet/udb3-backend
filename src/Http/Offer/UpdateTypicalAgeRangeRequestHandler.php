<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalAgeRange as EventUpdateTypicalAgeRange;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange as PlaceUpdateTypicalAgeRange;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateTypicalAgeRangeRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private AbstractUpdateTypicalAgeRange $updateTypicalAgeRange;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // @todo Use a data validator and change to an exception so it can be converted to an API problem
        if (empty($bodyContent->typicalAgeRange)) {
            return new JsonResponse(['error' => 'typicalAgeRange required'], StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        $bodyContent = Json::decode($request->getBody()->getContents());
        $ageRange = AgeRange::fromString($bodyContent->typicalAgeRange);

        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();

        if ($routeParameters->getOfferType() === OfferType::event()) {
            $this->updateTypicalAgeRange = new EventUpdateTypicalAgeRange(
                $offerId,
                $ageRange
            );
        } else {
            $this->updateTypicalAgeRange = new PlaceUpdateTypicalAgeRange(
                $offerId,
                $ageRange
            );
        }

        $this->commandBus->dispatch($this->updateTypicalAgeRange);

        return new NoContentResponse();
    }
}
