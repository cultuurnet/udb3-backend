<?php

namespace CultuurNet\UDB3\Label\Events;

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
     */
    public static function map()
    {
        return [
            CopyCreated::class => 'application/vnd.cultuurnet.udb3-events.label-copy-created+json',
            Created::class => 'application/vnd.cultuurnet.udb3-events.label-created+json',
            MadeInvisible::class => 'application/vnd.cultuurnet.udb3-events.label-made-invisible+json',
            MadePrivate::class => 'application/vnd.cultuurnet.udb3-events.label-made-private+json',
            MadePublic::class => 'application/vnd.cultuurnet.udb3-events.label-made-public+json',
            MadeVisible::class => 'application/vnd.cultuurnet.udb3-events.label-made-visible+json',
        ];
    }
}
