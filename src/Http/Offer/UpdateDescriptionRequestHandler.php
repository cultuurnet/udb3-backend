<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Deserializer\DeserializerInterface;
use CultuurNet\UDB3\Event\Commands\UpdateDescription as EventUpdateDescription;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Place\Commands\UpdateDescription as PlaceUpdateDescription;
use CultuurNet\UDB3\StringLiteral;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateDescriptionRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private DeserializerInterface $descriptionJsonDeserializer;

    public function __construct(
        CommandBus $commandBus,
        DeserializerInterface $descriptionJsonDeserializer
    ) {
        $this->commandBus = $commandBus;
        $this->descriptionJsonDeserializer = $descriptionJsonDeserializer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $language = Language::fromUdb3ModelLanguage($routeParameters->getLanguage());
        $bodyContent = $request->getBody()->getContents();
        $description = $this->descriptionJsonDeserializer->deserialize(new StringLiteral($bodyContent));

        if ($routeParameters->getOfferType()->sameAs(OfferType::event())) {
            $updateDescription = new EventUpdateDescription(
                $offerId,
                $language,
                $description
            );
        } else {
            $updateDescription = new PlaceUpdateDescription(
                $offerId,
                $language,
                $description,
            );
        }

        $this->commandBus->dispatch($updateDescription);
        return new NoContentResponse();
    }
}
