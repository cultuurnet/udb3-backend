<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Offer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class UpdateOrganizerFromJsonBodyRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private DocumentRepository $organizerDocumentRepository;

    public function __construct(CommandBus $commandBus, DocumentRepository $organizerDocumentRepository)
    {
        $this->commandBus = $commandBus;
        $this->organizerDocumentRepository = $organizerDocumentRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $bodyContent = Json::decode($request->getBody()->getContents());
        if (empty($bodyContent->organizer)) {
            throw ApiProblem::bodyInvalidDataWithDetail('organizer required');
        }
        $organizerId = $bodyContent->organizer;

        try {
            $this->organizerDocumentRepository->fetch($organizerId);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::urlNotFound('Organizer with id "' . $organizerId . '" does not exist.');
        }

        $this->commandBus->dispatch(new UpdateOrganizer($offerId, $organizerId));

        return new NoContentResponse();
    }
}
