<?php

namespace CultuurNet\UDB3\Http;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CheckOrganizerMiddleware implements MiddlewareInterface
{
    private DocumentRepository $organizerRepository;

    public function __construct(DocumentRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        if ($routeParameters->hasOrganizerId()) {
            $organizerId = $routeParameters->getOrganizerId();
            $this->organizerRepository->fetch($organizerId);
        }

        return $handler->handle($request);
    }
}
