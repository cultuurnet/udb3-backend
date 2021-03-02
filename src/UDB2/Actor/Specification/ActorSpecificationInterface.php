<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\Actor\Specification;

interface ActorSpecificationInterface
{
    /**
     * @return bool
     */
    public function isSatisfiedBy(\CultureFeed_Cdb_Item_Actor $actor);
}
