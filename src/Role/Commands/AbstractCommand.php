<?php

namespace CultuurNet\UDB3\Role\Commands;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use ValueObjects\Identity\UUID;

abstract class AbstractCommand implements AuthorizableCommandInterface
{
    /**
     * @var UUID
     */
    private $uuid;

    /**
     * AbstractCommand constructor.
     * @param UUID $uuid
     */
    public function __construct(UUID $uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return UUID
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @inheritdoc
     */
    public function getItemId()
    {
        return (string) $this->getUuid();
    }

    /**
     * @inheritdoc
     */
    public function getPermission()
    {
        return Permission::GEBRUIKERS_BEHEREN();
    }
}
