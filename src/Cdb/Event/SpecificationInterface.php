<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Cdb\Event;

use CultureFeed_Cdb_Item_Event;

/**
 * Interface for specifications on cdbxml events.
 */
interface SpecificationInterface
{
    /**
     * @return bool
     */
    public function isSatisfiedByEvent(CultureFeed_Cdb_Item_Event $event);
}
