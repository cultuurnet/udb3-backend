<?php

namespace CultuurNet\UDB3\Cdb\Event;

use CultureFeed_Cdb_Item_Event;

class Any implements SpecificationInterface
{
    public function isSatisfiedByEvent(CultureFeed_Cdb_Item_Event $event)
    {
        return true;
    }
}
