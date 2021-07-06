<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractCommand implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    protected $itemId;

    public function __construct(string $itemId)
    {
        if (!is_string($itemId)) {
            throw new \InvalidArgumentException(
                'Expected itemId to be a string, received ' . gettype($itemId)
            );
        }

        $this->itemId = $itemId;
    }

    public function getItemId(): string
    {
        return $this->itemId;
    }

    public function getPermission(): Permission
    {
        return Permission::AANBOD_BEWERKEN();
    }
}
