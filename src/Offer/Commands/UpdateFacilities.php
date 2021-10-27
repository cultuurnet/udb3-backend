<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class UpdateFacilities extends AbstractCommand
{
    /**
     * Facilities to be added.
     * @var array
     */
    protected $facilities;

    public function __construct(string $itemId, array $facilities)
    {
        parent::__construct($itemId);
        $this->facilities = $facilities;
    }

    public function getFacilities(): array
    {
        return $this->facilities;
    }

    public function getPermission(): Permission
    {
        return Permission::VOORZIENINGEN_BEWERKEN();
    }
}
