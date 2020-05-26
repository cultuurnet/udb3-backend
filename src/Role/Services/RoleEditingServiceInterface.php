<?php

namespace CultuurNet\UDB3\Role\Services;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use CultuurNet\UDB3\ValueObject\SapiVersion;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface RoleEditingServiceInterface
{
    public function create(StringLiteral $name): UUID;

    public function rename(UUID $uuid, StringLiteral $name): void;

    public function addPermission(UUID $uuid, Permission $permission): void;

    public function removePermission(UUID $uuid, Permission $permission): void;

    public function addUser(UUID $uuid, StringLiteral $userId): void;

    public function removeUser(UUID $uuid, StringLiteral $userId): void;

    public function addConstraint(UUID $uuid, SapiVersion $sapiVersion, Query $query): void;

    public function updateConstraint(UUID $uuid, SapiVersion $sapiVersion, Query $query): void;

    public function removeConstraint(UUID $uuid, SapiVersion $sapiVersion): void;

    public function addLabel(UUID $uuid, UUID $labelId): void;

    public function removeLabel(UUID $uuid, UUID $labelId): void;

    public function delete(UUID $uuid): void;
}
