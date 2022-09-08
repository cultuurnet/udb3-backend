<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Services;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\ReadModel\JsonDocument;

interface RoleReadingServiceInterface
{
    public function getLabelsByRoleUuid(UUID $uuid): JsonDocument;

    public function getUsersByRoleUuid(UUID $uuid): JsonDocument;

    public function getRolesByUserId(string $userId): JsonDocument;
}
