<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractPermissionEvent extends AbstractEvent
{
    private Permission $permission;

    final public function __construct(UUID $uuid, Permission $permission)
    {
        parent::__construct($uuid);
        $this->permission = $permission;
    }

    public function getPermission(): Permission
    {
        return $this->permission;
    }

    public static function deserialize(array $data): AbstractPermissionEvent
    {
        return new static(new UUID($data['uuid']), new Permission($data['permission']));
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'permission' => $this->permission->toString(),
        ];
    }
}
