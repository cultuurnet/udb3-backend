<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Organizer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Verenigingsloket\Exception\VerenigingsloketApiFailure;
use CultuurNet\UDB3\Verenigingsloket\VerenigingsloketConnector;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DeleteVerenigingsloketConnectionRequestHandler implements RequestHandlerInterface
{
    public function __construct(private readonly VerenigingsloketConnector $api, private readonly PermissionVoter $voter, private readonly string $currentUserId)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $organizerId = $routeParameters->getOrganizerId();

        if (! $this->voter->isAllowed(Permission::aanbodBewerken(), $organizerId, $this->currentUserId)) {
            throw ApiProblem::cannotDeleteVerenigingsloketMatch();
        }

        try {
            $success = $this->api->breakRelationFromVerenigingsloket(new Uuid($organizerId), $this->currentUserId);
        } catch (VerenigingsloketApiFailure) {
            throw ApiProblem::verenigingsloketApiFailure();
        }

        if ($success === false) {
            throw ApiProblem::verenigingsloketMatchNotFound($organizerId);
        }

        return new JsonResponse(
            [],
            StatusCodeInterface::STATUS_NO_CONTENT
        );
    }
}
