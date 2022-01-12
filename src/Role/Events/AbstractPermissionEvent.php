<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\Events;

use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\Identity\UUID;

abstract class AbstractPermissionEvent extends AbstractEvent
{
    /**
     * @var Permission
     */
    private $permission;

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
