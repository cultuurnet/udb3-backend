<?php

namespace CultuurNet\UDB3\Role\Services;

use CultuurNet\UDB3\ReadModel\JsonDocument;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface RoleReadingServiceInterface
{
    /**
     * @return JsonDocument
     */
    public function getLabelsByRoleUuid(UUID $uuid);

    /**
     * @return JsonDocument
     */
    public function getUsersByRoleUuid(UUID $uuid);

    /**
     * @return JsonDocument
     */
    public function getRolesByUserId(StringLiteral $userId);
}
