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
     * @param CultureFeed_Cdb_Item_Event $event
     * @return bool
     */
    public function isSatisfiedByEvent(CultureFeed_Cdb_Item_Event $event);
}
