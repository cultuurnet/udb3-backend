<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Role\Events\RoleDetailsProjectedToJSONLD;

interface DocumentEventFactory
{
    /**
     * @return EventProjectedToJSONLD|OrganizerProjectedToJSONLD|PlaceProjectedToJSONLD|RoleDetailsProjectedToJSONLD
     */
    public function createEvent(string $id);
}
