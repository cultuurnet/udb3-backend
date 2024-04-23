<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonLdResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetOwnershipRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $ownershipRepository;

    public function __construct(DocumentRepository $ownershipRepository)
    {
        $this->ownershipRepository = $ownershipRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $ownershipId = $routeParameters->getOwnershipId();

        try {
            return new JsonLdResponse(
                $this->ownershipRepository->fetch($ownershipId)->getRawBody()
            );
        } catch (DocumentDoesNotExist $exception) {
            throw ApiProblem::ownershipNotFound($ownershipId);
        }
    }
}
