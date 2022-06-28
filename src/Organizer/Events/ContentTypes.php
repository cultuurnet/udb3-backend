<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;

class ContentTypes
{
    /**
     * Intentionally made private.
     */
    private function __construct()
    {
    }

    /**
     * @return array
     *
     * @todo once we upgrade to PHP 5.6+ this can be moved to a constant.
     */
    public static function map()
    {
        return [
            OrganizerProjectedToJSONLD::class => 'application/vnd.cultuurnet.udb3-events.organizer-projected-to-jsonld+json',
        ];
    }
}
