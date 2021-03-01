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
            OrganizerCreated::class => 'application/vnd.cultuurnet.udb3-events.organizer-created+json',
            OrganizerCreatedWithUniqueWebsite::class => 'application/vnd.cultuurnet.udb3-events.organizer-created-with-unique-website+json',
            OrganizerDeleted::class => 'application/vnd.cultuurnet.udb3-events.organizer-deleted+json',
            OrganizerImportedFromUDB2::class => 'application/vnd.cultuurnet.udb3-events.organizer-imported-from-udb2+json',
            OrganizerUpdatedFromUDB2::class => 'application/vnd.cultuurnet.udb3-events.organizer-updated-from-udb2+json',
            OrganizerProjectedToJSONLD::class => 'application/vnd.cultuurnet.udb3-events.organizer-projected-to-jsonld+json',
            WebsiteUpdated::class => 'application/vnd.cultuurnet.udb3-events.organizer-website-updated+json',
            TitleUpdated::class => 'application/vnd.cultuurnet.udb3-events.organizer-title-updated+json',
            TitleTranslated::class => 'application/vnd.cultuurnet.udb3-events.organizer-title-translated+json',
            LabelAdded::class => 'application/vnd.cultuurnet.udb3-events.organizer-label-added+json',
            LabelRemoved::class => 'application/vnd.cultuurnet.udb3-events.organizer-label-removed+json',
            AddressUpdated::class => 'application/vnd.cultuurnet.udb3-events.organizer-address-updated+json',
            AddressRemoved::class => 'application/vnd.cultuurnet.udb3-events.organizer-address-removed+json',
            AddressTranslated::class => 'application/vnd.cultuurnet.udb3-events.organizer-address-translated+json',
            ContactPointUpdated::class => 'application/vnd.cultuurnet.udb3-events.organizer-contact-point-updated+json',
        ];
    }
}
