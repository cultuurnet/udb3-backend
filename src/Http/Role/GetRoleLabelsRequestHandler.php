<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetRoleLabelsRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $roleLabelsReadRepository;

    public function __construct(DocumentRepository $roleLabelsReadRepository)
    {
        $this->roleLabelsReadRepository = $roleLabelsReadRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameter = new RouteParameters($request);
        $roleId = $routeParameter->getRoleId();

        $document = $this->roleLabelsReadRepository->fetch($roleId);

        return new JsonResponse(array_values($document->getAssocBody()));
    }
}
