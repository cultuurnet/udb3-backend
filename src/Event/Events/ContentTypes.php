<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;

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
            EventProjectedToJSONLD::class => 'application/vnd.cultuurnet.udb3-events.event-projected-to-jsonld+json',
        ];
    }
}
