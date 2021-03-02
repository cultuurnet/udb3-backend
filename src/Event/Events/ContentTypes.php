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
            AudienceUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-audience-updated+json',
            BookingInfoUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-booking-info-updated+json',
            PriceInfoUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-price-info-updated.json',
            ContactPointUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-contact-point-updated+json',
            DescriptionTranslated::class => 'application/vnd.cultuurnet.udb3-events.event-description-translated+json',
            DescriptionUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-description-updated+json',
            EventCreated::class => 'application/vnd.cultuurnet.udb3-events.event-created+json',
            EventCopied::class => 'application/vnd.cultuurnet.udb3-events.event-copied+json',
            EventDeleted::class => 'application/vnd.cultuurnet.udb3-events.event-deleted+json',
            EventImportedFromUDB2::class => 'application/vnd.cultuurnet.udb3-events.event-imported-from-udb2+json',
            EventProjectedToJSONLD::class => 'application/vnd.cultuurnet.udb3-events.event-projected-to-jsonld+json',
            EventUpdatedFromUDB2::class => 'application/vnd.cultuurnet.udb3-events.event-updated-from_udb2+json',
            ImageAdded::class => 'application/vnd.cultuurnet.udb3-events.event-image-added+json',
            ImageRemoved::class => 'application/vnd.cultuurnet.udb3-events.event-image-removed+json',
            ImageUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-image-updated+json',
            LabelAdded::class => 'application/vnd.cultuurnet.udb3-events.event-label-added+json',
            LabelRemoved::class => 'application/vnd.cultuurnet.udb3-events.event-label-removed+json',
            MainImageSelected::class => 'application/vnd.cultuurnet.udb3-events.event-main-image-selected+json',
            MajorInfoUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-major-info-updated+json',
            LocationUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-location-updated+json',
            GeoCoordinatesUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-geocoordinates-updated+json',
            CalendarUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-calendar-updated+json',
            OrganizerDeleted::class => 'application/vnd.cultuurnet.udb3-events.event-organizer-deleted+json',
            OrganizerUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-organizer-updated+json',
            TitleTranslated::class => 'application/vnd.cultuurnet.udb3-events.event-title-translated+json',
            TitleUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-title-updated+json',
            TypicalAgeRangeUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-typical-age-range-updated+json',
            TypicalAgeRangeDeleted::class => 'application/vnd.cultuurnet.udb3-events.event-typical-age-range-deleted+json',
            TypeUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-type-updated+json',
            ThemeUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-theme-updated+json',
            FacilitiesUpdated::class => 'application/vnd.cultuurnet.udb3-events.event-facilities-updated+json',
            // Moderation
            Published::class => 'application/vnd.cultuurnet.udb3-events.moderation.event-published+json',
            Approved::class => 'application/vnd.cultuurnet.udb3-events.moderation.event-approved+json',
            Rejected::class => 'application/vnd.cultuurnet.udb3-events.moderation.event-rejected+json',
            FlaggedAsDuplicate::class => 'application/vnd.cultuurnet.udb3-events.moderation.event-flagged-as-duplicate+json',
            FlaggedAsInappropriate::class => 'application/vnd.cultuurnet.udb3-events.moderation.event-flagged-as-inappropriate+json',
            Concluded::class => 'application/vnd.cultuurnet.udb3-events.event-concluded+json',
        ];
    }
}
