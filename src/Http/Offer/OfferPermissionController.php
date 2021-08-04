<?php

declare(strict_types=1);
/**
 * @deprecated
 */

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class OfferPermissionController
{
    /**
     * @var Permission
     */
    private $permission;

    /**
     * @var PermissionVoter
     */
    private $permissionVoter;

    /**
     * @var StringLiteral
     */
    private $currentUserId;


    public function __construct(
        Permission $permission,
        PermissionVoter $permissionVoter,
        StringLiteral $currentUserId = null
    ) {
        $this->permission = $permission;
        $this->permissionVoter = $permissionVoter;
        $this->currentUserId = $currentUserId;
    }

    /**
     * @param string $offerId
     * @return Response
     */
    public function currentUserHasPermission($offerId)
    {
        return $this->hasPermission(
            new StringLiteral((string) $offerId),
            $this->currentUserId
        );
    }

    /**
     * @param string $offerId
     * @param string $userId
     * @return Response
     */
    public function givenUserHasPermission($offerId, $userId)
    {
        return $this->hasPermission(
            new StringLiteral((string) $offerId),
            new StringLiteral((string) $userId)
        );
    }

    /**
     * @param StringLiteral $offerId
     * @param StringLiteral|null $userId
     * @return Response
     */
    private function hasPermission($offerId, $userId = null)
    {
        if ($userId) {
            $hasPermission = $this->permissionVoter->isAllowed(
                $this->permission,
                $offerId,
                $userId
            );
        } else {
            $hasPermission = false;
        }

        return JsonResponse::create(['hasPermission' => $hasPermission])
            ->setPrivate();
    }
}
