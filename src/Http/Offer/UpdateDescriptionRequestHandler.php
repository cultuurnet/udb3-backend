<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Description as LegacyDescription;
use CultuurNet\UDB3\Event\Commands\UpdateDescription as EventUpdateDescription;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Text\Description;
use CultuurNet\UDB3\Model\ValueObject\Text\DescriptionShouldNotBeEmpty;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use CultuurNet\UDB3\Offer\OfferType;
use CultuurNet\UDB3\Offer\Serializers\DescriptionDenormalizer;
use CultuurNet\UDB3\Place\Commands\UpdateDescription as PlaceUpdateDescription;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateDescriptionRequestHandler implements RequestHandlerInterface
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
        $language = $routeParameters->getLanguage();

        $requestBodyParser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(
                JsonSchemaLocator::getSchemaFileByOfferType(
                    $offerType,
                    JsonSchemaLocator::EVENT_DESCRIPTION_PUT,
                    JsonSchemaLocator::PLACE_DESCRIPTION_PUT,
                )
            ),
            new DenormalizingRequestBodyParser(new DescriptionDenormalizer(), Description::class)
        );

        try {
            $request = $requestBodyParser->parse($request);

            /** @var Description $description */
            $description = $request->getParsedBody();

            if ($offerType->sameAs(OfferType::event())) {
                $updateDescription = new EventUpdateDescription(
                    $offerId,
                    $language,
                    LegacyDescription::fromUdb3ModelDescription($description)
                );
            } else {
                $updateDescription = new PlaceUpdateDescription(
                    $offerId,
                    $language,
                    LegacyDescription::fromUdb3ModelDescription($description)
                );
            }

            $this->commandBus->dispatch($updateDescription);
        } catch (DescriptionShouldNotBeEmpty $exception) {
            $deleteDescription = new DeleteDescription(
                $offerId,
                $language,
            );
            $this->commandBus->dispatch($deleteDescription);
        }

        return new NoContentResponse();
    }
}
