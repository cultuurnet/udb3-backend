<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Offer\Security\Permission\PermissionVoterInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class OfferPermissionsController
{
    /**
     * @var Permission[]
     */
    private $permissions;

    /**
     * @var PermissionVoterInterface
     */
    private $permissionVoter;

    /**
     * @var StringLiteral|null
     */
    private $currentUserId;

    /**
     * @param Permission[] $permissions
     */
    public function __construct(
        array $permissions,
        PermissionVoterInterface $permissionVoter,
        StringLiteral $currentUserId = null
    ) {
        $this->permissions = $permissions;
        $this->permissionVoter = $permissionVoter;
        $this->currentUserId = $currentUserId;
    }

    /**
     * @param string $offerId
     * @return Response
     */
    public function getPermissionsForCurrentUser($offerId)
    {
        if (is_null($this->currentUserId)) {
            return JsonResponse::create(['permissions' => []])->setPrivate();
        }

        return $this->getPermissions(
            new StringLiteral($offerId),
            $this->currentUserId
        );
    }

    /**
     * @param string $offerId
     * @param string $userId
     * @return Response
     */
    public function getPermissionsForGivenUser($offerId, $userId)
    {
        return $this->getPermissions(
            new StringLiteral($offerId),
            new StringLiteral($userId)
        );
    }

    /**
     * @return Response
     */
    private function getPermissions(StringLiteral $offerId, StringLiteral $userId)
    {
        $permissionsToReturn = [];
        foreach ($this->permissions as $permission) {
            $hasPermission = $this->permissionVoter->isAllowed(
                $permission,
                $offerId,
                $userId
            );

            if ($hasPermission) {
                $permissionsToReturn[] = (string) $permission;
            }
        }

        return JsonResponse::create(['permissions' => $permissionsToReturn])
            ->setPrivate();
    }
}
