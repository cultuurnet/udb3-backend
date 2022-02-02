<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Roles;

use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Role\Events\LabelAdded;
use CultuurNet\UDB3\Role\Events\LabelRemoved;
use CultuurNet\UDB3\Role\Events\RoleDeleted;
use ValueObjects\Identity\UUID as LegacyUUID;

class LabelRolesProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var LabelRolesWriteRepositoryInterface
     */
    private $labelRolesWriteRepository;

    /**
     * LabelRolesProjector constructor.
     */
    public function __construct(
        LabelRolesWriteRepositoryInterface $labelRolesWriteRepository
    ) {
        $this->labelRolesWriteRepository = $labelRolesWriteRepository;
    }


    protected function applyLabelAdded(LabelAdded $labelAdded)
    {
        $this->labelRolesWriteRepository->insertLabelRole(
            new LegacyUUID($labelAdded->getLabelId()->toString()),
            new LegacyUUID($labelAdded->getUuid()->toString())
        );
    }


    protected function applyLabelRemoved(LabelRemoved $labelRemoved)
    {
        $this->labelRolesWriteRepository->removeLabelRole(
            new LegacyUUID($labelRemoved->getLabelId()->toString()),
            new LegacyUUID($labelRemoved->getUuid()->toString())
        );
    }


    protected function applyRoleDeleted(RoleDeleted $roleDeleted)
    {
        $this->labelRolesWriteRepository->removeRole(
            new LegacyUUID($roleDeleted->getUuid()->toString())
        );
    }
}
