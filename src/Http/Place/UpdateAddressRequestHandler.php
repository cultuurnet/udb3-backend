<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Place;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Http\Deserializer\Address\AddressJSONDeserializer;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\StringLiteral;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateAddressRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    private DocumentRepository $placeDocumentRepository;

    public function __construct(CommandBus $commandBus, DocumentRepository $placeDocumentRepository)
    {
        $this->commandBus = $commandBus;
        $this->placeDocumentRepository = $placeDocumentRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $placeId = $routeParameters->getPlaceId();
        $language = $routeParameters->getLanguage();

        $address = (new AddressJSONDeserializer())->deserialize(
            new StringLiteral($request->getBody()->getContents())
        );

        try {
            $this->placeDocumentRepository->fetch($placeId);
        } catch (DocumentDoesNotExist $e) {
            throw new EntityNotFoundException(
                sprintf('Place with id: %s not found.', $placeId)
            );
        }

        $this->commandBus->dispatch(
            new UpdateAddress($placeId, $address, Language::fromUdb3ModelLanguage($language))
        );

        return new NoContentResponse();
    }
}
