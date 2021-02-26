<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use ValueObjects\Identity\UUID;

interface LabelRolesWriteRepositoryInterface
{
    public function insertLabelRole(UUID $labelId, UUID $roleId);


    public function removeLabelRole(UUID $labelId, UUID $roleId);


    public function removeRole(UUID $roleId);
}
