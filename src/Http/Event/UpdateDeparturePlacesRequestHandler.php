<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateDeparturePlaces;
use CultuurNet\UDB3\Event\IncompatibleAudienceType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Body\DenormalizingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaLocator;
use CultuurNet\UDB3\Http\Request\Body\JsonSchemaValidatingRequestBodyParser;
use CultuurNet\UDB3\Http\Request\Body\RequestBodyParserFactory;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Web\UrlsDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Web\Urls;
use CultuurNet\UDB3\Offer\IriOfferIdentifierFactory;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateDeparturePlacesRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly DocumentRepository $placeDocumentRepository,
        private readonly IriOfferIdentifierFactory $iriOfferIdentifierFactory,
        private readonly DeparturePlacesLimitLogger $departurePlacesLimitLogger,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $eventId = $routeParameters->getEventId();

        $parser = RequestBodyParserFactory::createBaseParser(
            new JsonSchemaValidatingRequestBodyParser(JsonSchemaLocator::EVENT_DEPARTURE_PLACES_PUT),
            new DenormalizingRequestBodyParser(new UrlsDenormalizer(), Urls::class),
        );

        try {
            /** @var Urls $departurePlaces */
            $departurePlaces = $parser->parse($request)->getParsedBody();
        } catch (ApiProblem $apiProblem) {
            $this->departurePlacesLimitLogger->logIfLimitExceeded($apiProblem, $eventId, '/');
            throw $apiProblem;
        }

        $this->guardPlacesExist($departurePlaces);

        try {
            $this->commandBus->dispatch(
                new UpdateDeparturePlaces($eventId, $departurePlaces)
            );
        } catch (IncompatibleAudienceType $e) {
            throw ApiProblem::inCompatibleAudienceType($e->getMessage());
        }

        return new NoContentResponse();
    }

    private function guardPlacesExist(Urls $departurePlaces): void
    {
        $errors = [];

        foreach ($departurePlaces->toArray() as $index => $url) {
            $placeId = $this->iriOfferIdentifierFactory->fromIri($url)->getId();

            try {
                $this->placeDocumentRepository->fetch($placeId);
            } catch (DocumentDoesNotExist $e) {
                $errors[] = new SchemaError(
                    '/' . $index,
                    'The place with url "' . $url->toString() . '" was not found.'
                );
            }
        }

        if (!empty($errors)) {
            throw ApiProblem::bodyInvalidData(...$errors);
        }
    }
}
