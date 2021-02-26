<?php

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JwtUserToken;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use ValueObjects\StringLiteral\StringLiteral;

class UserPermissionsVoter implements VoterInterface
{
    /**
     * @var UserPermissionsReadRepositoryInterface
     */
    private $permissionsRepository;

    /**
     * PermissionVoter constructor.
     */
    public function __construct(UserPermissionsReadRepositoryInterface $permissionsRepository)
    {
        $this->permissionsRepository = $permissionsRepository;
    }

    /**
     * @inheritdoc
     */
    public function supportsAttribute($attribute)
    {
        return Permission::has($attribute);
    }

    /**
     * @inheritdoc
     */
    public function supportsClass($class)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $result = self::ACCESS_ABSTAIN;

        if (!($object instanceof Request)) {
            return $result;
        }

        if ($token instanceof JwtUserToken && $token->isAuthenticated()) {
            $userUuid = $token->getCredentials()->id();
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

            $permission = Permission::fromNative($attribute);

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
