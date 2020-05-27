<?php

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

class Conclude implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    protected $itemId;

    /**
     * Conclude constructor.
     *
     * @param string $itemId
     */
    public function __construct($itemId)
    {
        if (!is_string($itemId)) {
            throw new \InvalidArgumentException(
                'Expected itemId to be a string, received ' . gettype($itemId)
            );
        }

        $this->itemId = $itemId;
    }

    /**
     * @return string
     */
    public function getItemId()
    {
        return $this->itemId;
    }

    /**
     * @return Permission
     */
    public function getPermission()
    {
        return  Permission::AANBOD_MODEREREN();
    }
}
