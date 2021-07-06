<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

abstract class AbstractUpdateFacilities extends AbstractCommand
{
    /**
     * Facilities to be added.
     * @var array
     */
    protected $facilities;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, array $facilities)
    {
        parent::__construct($itemId);
        $this->facilities = $facilities;
    }

    /**
     * @return array
     */
    public function getFacilities()
    {
        return $this->facilities;
    }

    public function getPermission(): Permission
    {
        return Permission::VOORZIENINGEN_BEWERKEN();
    }
}
