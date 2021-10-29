<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class UpdateFacilities extends AbstractCommand
{
    /**
     * @var string[]
     */
    private array $facilityIds;

    public function __construct(string $itemId, array $facilityIds)
    {
        parent::__construct($itemId);
        $this->facilityIds = $facilityIds;
    }

    public function getFacilityIds(): array
    {
        return $this->facilityIds;
    }

    public function getPermission(): Permission
    {
        return Permission::VOORZIENINGEN_BEWERKEN();
    }
}
