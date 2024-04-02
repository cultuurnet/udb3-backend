<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UserId;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Ownership\Commands\RejectOwnership;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RejectOwnershipRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;
    private OwnershipSearchRepository $ownershipSearchRepository;
    private CurrentUser $currentUser;
    private PermissionVoter $permissionVoter;

    public function __construct(
        CommandBus $commandBus,
        OwnershipSearchRepository $ownershipSearchRepository,
        CurrentUser $currentUser,
        PermissionVoter $permissionVoter
    ) {
        $this->commandBus = $commandBus;
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->currentUser = $currentUser;
        $this->permissionVoter = $permissionVoter;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $ownershipId = $routeParameters->getOwnershipId();

        // Make sure there is an ownership
        try {
            $ownership = $this->ownershipSearchRepository->getById($ownershipId);
        } catch (OwnershipItemNotFound $exception) {
            throw ApiProblem::ownershipNotFound($ownershipId);
        }

        // Make sure the current user can reject the ownership
        // This means that the current user is a god user
        // Or that the current user is the owner of the item
        if (!$this->isAllowedToRejectOwnership($ownership)) {
            throw ApiProblem::forbidden('You are not allowed to reject this ownership');
        }

        $this->commandBus->dispatch(
            new RejectOwnership(new UUID($ownershipId), new UserId($this->currentUser->getId()))
        );

        return new JsonResponse(
            [],
            StatusCodeInterface::STATUS_NO_CONTENT
        );
    }

    private function isAllowedToRejectOwnership(OwnershipItem $ownership): bool
    {
        if ($this->currentUser->isGodUser()) {
            return true;
        }

        return $this->permissionVoter->isAllowed(
            Permission::organisatiesBeheren(),
            $ownership->getItemId(),
            $this->currentUser->getId()
        );
    }
}
