<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\Offer\Commands\AuthorizableCommandInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;

final class MergeProductions implements AuthorizableCommandInterface
{
    /**
     * @var ProductionId
     */
    private $from;

    /**
     * @var ProductionId
     */
    private $to;

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

    public function getItemId()
    {
        return $this->getFrom()->toNative();
    }

    public function getPermission()
    {
        Permission::PRODUCTIES_AANMAKEN();
    }
}
