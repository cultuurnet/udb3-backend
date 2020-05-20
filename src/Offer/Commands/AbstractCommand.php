<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractCommand implements AuthorizableCommandInterface
{
    /**
     * @var string
     */
    protected $itemId;

    /**
     * AbstractCommand constructor.
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
        return Permission::AANBOD_BEWERKEN();
    }
}
