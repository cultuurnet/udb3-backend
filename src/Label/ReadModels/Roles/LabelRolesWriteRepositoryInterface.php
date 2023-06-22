<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

interface LabelRolesWriteRepositoryInterface
{
    public function insertLabelRole(UUID $labelId, UUID $roleId): void;

    public function removeLabelRole(UUID $labelId, UUID $roleId): void;

    public function removeRole(UUID $roleId): void;
}
