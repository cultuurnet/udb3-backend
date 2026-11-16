<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetOrganizerRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $organizerRepository;

    public function __construct(DocumentRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $organizerId = $routeParameters->getOrganizerId();

        try {
            return new JsonLdResponse(
                $this->organizerRepository->fetch($organizerId)->getRawBody()
            );
        } catch (DocumentDoesNotExist $exception) {
            throw ApiProblem::organizerNotFound($organizerId);
        }
    }
}
