<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetRolesFromUserRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $userRolesRepository;

    public function __construct(DocumentRepository $userRolesRepository)
    {
        $this->userRolesRepository = $userRolesRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameter = new RouteParameters($request);
        $userId = $routeParameter->getUserId();

        try {
            $document = $this->userRolesRepository->fetch($userId);
        } catch (DocumentDoesNotExist $e) {
            // It's possible the document does not exist if the user exists but has
            // no roles, since we don't have a "UserCreated" event to listen to and
            // we can't create an empty document of roles in the projector.
            // @todo Should we check if the user exists using culturefeed?
            // @see https://jira.uitdatabank.be/browse/III-1292
            return new JsonResponse([]);
        }

        return new JsonResponse(array_values($document->getAssocBody()));
    }
}
