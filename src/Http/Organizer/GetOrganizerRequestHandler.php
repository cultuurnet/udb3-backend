<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetOrganizerRequestHandler implements RequestHandlerInterface
{
    private EntityServiceInterface $organizerService;

    public function __construct(EntityServiceInterface $organizerService)
    {
        $this->organizerService = $organizerService;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $organizerId = $routeParameters->getOrganizerId();

        try {
            return new JsonLdResponse(
                $this->organizerService->getEntity($organizerId)
            );
        } catch (EntityNotFoundException $exception) {
            throw ApiProblem::organizerNotFound($organizerId);
        }
    }
}
