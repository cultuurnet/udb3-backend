<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;

interface LabelRolesWriteRepositoryInterface
{
    public function insertLabelRole(Uuid $labelId, Uuid $roleId): void;

    public function removeLabelRole(Uuid $labelId, Uuid $roleId): void;

    public function removeRole(Uuid $roleId): void;
}
