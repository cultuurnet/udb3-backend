<?php

declare(strict_types=1);

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
     * @todo once we upgrade to PHP 5.6+ this can be moved to a constant.
     */
    public static function map(): array
    {
        return [
            MediaObjectCreated::class => 'application/vnd.cultuurnet.udb3-events.media-object-created+json',
        ];
    }
}
