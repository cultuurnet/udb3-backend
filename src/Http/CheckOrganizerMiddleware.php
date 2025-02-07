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
    private DocumentRepository $offerRepository;

    public function __construct(DocumentRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
    }
}
