<?php

namespace CultuurNet\UDB3\Media\Events;

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
            MediaObjectCreated::class => 'application/vnd.cultuurnet.udb3-events.media-object-created+json',
        ];
    }
}
