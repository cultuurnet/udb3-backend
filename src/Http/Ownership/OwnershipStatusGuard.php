<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Ownership;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItem;
use CultuurNet\UDB3\Ownership\Repositories\OwnershipItemNotFound;
use CultuurNet\UDB3\Ownership\Repositories\Search\OwnershipSearchRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\User\CurrentUser;

final class OwnershipStatusGuard
{
    private OwnershipSearchRepository $ownershipSearchRepository;
    private PermissionVoter $permissionVoter;

    public function __construct(
        OwnershipSearchRepository $ownershipSearchRepository,
        PermissionVoter $permissionVoter
    ) {
        $this->ownershipSearchRepository = $ownershipSearchRepository;
        $this->permissionVoter = $permissionVoter;
    }

    public function isAllowedToGetCreator(string $itemId, CurrentUser $currentUser): void
    {
        $isAllowed = $this->permissionVoter->isAllowed(
            Permission::organisatiesBeheren(),
            $itemId,
            $currentUser->getId()
        );

        if (!$isAllowed) {
            throw ApiProblem::forbidden('You are not allowed to get creator for this item');
        }
    }

    public function isAllowedToRequest(string $itemId, string $requesterId, CurrentUser $currentUser): void
    {
        $isOwner = $this->permissionVoter->isAllowed(
            Permission::organisatiesBeheren(),
            $itemId,
            $currentUser->getId()
        );

        if (!$isOwner && $currentUser->getId() !== $requesterId) {
            throw ApiProblem::forbidden('You are not allowed to request ownership for this item');
        }
    }

    public function isAllowedToApprove(string $ownershipId, CurrentUser $currentUser): void
    {
        $this->isAllowed($ownershipId, $currentUser, 'approve');
    }

    public function isAllowedToReject(string $ownershipId, CurrentUser $currentUser): void
    {
        $this->isAllowed($ownershipId, $currentUser, 'reject');
    }

    public function isAllowedToDelete(string $ownershipId, CurrentUser $currentUser): void
    {
        $this->isAllowed($ownershipId, $currentUser, 'delete');
    }

    private function isAllowed(string $ownershipId, CurrentUser $currentUser, string $change): void
    {
        try {
            $ownership = $this->ownershipSearchRepository->getById($ownershipId);
        } catch (OwnershipItemNotFound $exception) {
            throw ApiProblem::ownershipNotFound($ownershipId);
        }

        if (!$this->isAllowedToUpdateOwnership($ownership, $currentUser)) {
            throw ApiProblem::forbidden('You are not allowed to ' . $change . ' this ownership');
        }
    }

    private function isAllowedToUpdateOwnership(OwnershipItem $ownership, CurrentUser $currentUser): bool
    {
        if ($currentUser->isGodUser()) {
            return true;
        }

        return $this->permissionVoter->isAllowed(
            Permission::organisatiesBeheren(),
            $ownership->getItemId(),
            $currentUser->getId()
        );
    }
}
