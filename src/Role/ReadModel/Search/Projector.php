<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Role\ReadModel\Search;

use Broadway\EventHandling\EventListener;
use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Role\Events\ConstraintAdded;
use CultuurNet\UDB3\Role\Events\ConstraintRemoved;
use CultuurNet\UDB3\Role\Events\ConstraintUpdated;
use CultuurNet\UDB3\Role\Events\RoleCreated;
use CultuurNet\UDB3\Role\Events\RoleRenamed;
use CultuurNet\UDB3\Role\Events\RoleDeleted;

class Projector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    private RepositoryInterface $repository;

    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function applyRoleCreated(
        RoleCreated $roleCreated,
        DomainMessage $domainMessage
    ): void {
        $this->repository->save(
            $roleCreated->getUuid()->toString(),
            $roleCreated->getName()->toNative()
        );
    }

    public function applyRoleRenamed(
        RoleRenamed $roleRenamed,
        DomainMessage $domainMessage
    ): void {
        $this->repository->updateName(
            $roleRenamed->getUuid()->toString(),
            $roleRenamed->getName()->toNative()
        );
    }

    public function applyRoleDeleted(
        RoleDeleted $roleDeleted,
        DomainMessage $domainMessage
    ): void {
        $this->repository->remove($roleDeleted->getUuid()->toString());
    }

    protected function applyConstraintAdded(ConstraintAdded $constraintAdded): void
    {
        $this->repository->updateConstraint(
            $constraintAdded->getUuid()->toString(),
            $constraintAdded->getQuery()
        );
    }

    protected function applyConstraintUpdated(ConstraintUpdated $constraintUpdated): void
    {
        $this->repository->updateConstraint(
            $constraintUpdated->getUuid()->toString(),
            $constraintUpdated->getQuery()
        );
    }

    protected function applyConstraintRemoved(ConstraintRemoved $constraintRemoved): void
    {
        $this->repository->updateConstraint(
            $constraintRemoved->getUuid()->toString()
        );
    }
}
