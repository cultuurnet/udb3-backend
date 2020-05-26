<?php

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use ValueObjects\Identity\UUID;

interface LabelRolesWriteRepositoryInterface
{
    /**
     * @param UUID $labelId
     * @param UUID $roleId
     */
    public function insertLabelRole(UUID $labelId, UUID $roleId);

    /**
     * @param UUID $labelId
     * @param UUID $roleId
     */
    public function removeLabelRole(UUID $labelId, UUID $roleId);

    /**
     * @param UUID $roleId
     */
    public function removeRole(UUID $roleId);
}
