<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionsVoter implements VoterInterface
{
    private UserPermissionsReadRepositoryInterface $permissionsRepository;

    /**
     * PermissionVoter constructor.
     */
    public function __construct(UserPermissionsReadRepositoryInterface $permissionsRepository)
    {
        $this->permissionsRepository = $permissionsRepository;
    }

    /**
     * @param Permission $attribute
     */
    public function supportsAttribute($attribute): bool
    {
        return in_array($attribute->toString(), Permission::getAllowedValues(), true);
    }

    public function supportsClass($class): bool
    {
        return true;
    }

    /**
     * @param Permission[] $attributes
     */
    public function vote(TokenInterface $token, $object, array $attributes): int
    {
        $result = self::ACCESS_ABSTAIN;

        if (!($object instanceof Request)) {
            return $result;
        }

        if ($token instanceof JsonWebToken && $token->isAuthenticated()) {
            $userUuid = $token->getUserId();
        } else {
            return $result;
        }

        $userPermissions = $this->permissionsRepository->getPermissions(new StringLiteral($userUuid));
        $missingPermissions = [];

        foreach ($attributes as $attribute) {
            // these attributes come from the access control rules in the security configuration
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $permission = $attribute;

            if (!in_array($permission, $userPermissions)) {
                // collect any missing permissions to revert access after we checked all matching attributes
                $missingPermissions[] = $permission;
            } else {
                // grant access when we encounter an attribute that matches a permission
                $result = self::ACCESS_GRANTED;
            }
        }

        if (!empty($missingPermissions)) {
            $result = self::ACCESS_DENIED;
        }

        return $result;
    }
}
