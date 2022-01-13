<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Security\AuthorizableCommand;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class MergeProductions implements AuthorizableCommand
{
    private ProductionId $from;

    private ProductionId $to;

    public function __construct(
        ProductionId $from,
        ProductionId $to
    ) {
        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): ProductionId
    {
        return $this->from;
    }

    public function getTo(): ProductionId
    {
        return $this->to;
    }

    public function getItemId(): string
    {
        return $this->getFrom()->toNative();
    }

    public function getPermission(): Permission
    {
        return Permission::productiesAanmaken();
    }
}
