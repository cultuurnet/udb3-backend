<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Management;

use CultuurNet\UDB3\Jwt\Symfony\Authentication\JsonWebToken;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class PermissionsVoter implements VoterInterface
{
    /**
     * @var string[][]
     */
    private array $authorizationList;

    /**
     * @param string[][] $authorizationList
     */
    public function __construct(array $authorizationList)
    {
        $this->authorizationList = $authorizationList;
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

        foreach ($attributes as $attribute) {
            // these attributes come from the access control rules in the security configuration
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            if (in_array($userUuid, $this->authorizationList['allow_all'])) {
                $result = self::ACCESS_GRANTED;
            } else {
                $result = self::ACCESS_DENIED;
            }
        }

        return $result;
    }
}
