<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\StringLiteral;

class AnyOfVoter implements PermissionVoter
{
    /**
     * @var PermissionVoter[]
     */
    private $voters;

    private array $cache = [];

    /**
     * @param PermissionVoter[] ...$voters
     */
    public function __construct(PermissionVoter ...$voters)
    {
        $this->voters = $voters;
    }

    public function isAllowed(
        Permission $permission,
        StringLiteral $itemId,
        StringLiteral $userId
    ): bool {
        $cacheKey = $itemId->toNative() . $userId->toNative();
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        foreach ($this->voters as $voter) {
            if ($voter->isAllowed($permission, $itemId, $userId)) {
                $this->cache[$cacheKey] = true;
                return true;
            }
        }

        $this->cache[$cacheKey] = false;
        return false;
    }
}
