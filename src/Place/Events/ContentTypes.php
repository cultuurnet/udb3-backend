<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Place\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected;

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
            AddressUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-address-updated+json',
            AddressTranslated::class => 'application/vnd.cultuurnet.udb3-events.place-address-translated+json',
            GeoCoordinatesUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-geocoordinates-updated+json',
            BookingInfoUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-booking-info-updated+json',
            PriceInfoUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-price-info-updated.json',
            ContactPointUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-contact-point-updated+json',
            DescriptionTranslated::class => 'application/vnd.cultuurnet.udb3-events.place-description-translated+json',
            DescriptionUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-description-updated+json',
            FacilitiesUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-facilities-updated+json',
            ImageAdded::class => 'application/vnd.cultuurnet.udb3-events.place-image-added+json',
            ImageRemoved::class => 'application/vnd.cultuurnet.udb3-events.place-image-removed+json',
            ImageUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-image-updated+json',
            LabelAdded::class => 'application/vnd.cultuurnet.udb3-events.place-label-added+json',
            LabelRemoved::class => 'application/vnd.cultuurnet.udb3-events.place-label-removed+json',
            MainImageSelected::class => 'application/vnd.cultuurnet.udb3-events.place-main-image-selected+json',
            MajorInfoUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-major-info-updated+json',
            CalendarUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-calendar-updated+json',
            OrganizerDeleted::class => 'application/vnd.cultuurnet.udb3-events.place-organizer-deleted+json',
            OrganizerUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-organizer-updated+json',
            PlaceCreated::class => 'application/vnd.cultuurnet.udb3-events.place-created+json',
            PlaceDeleted::class => 'application/vnd.cultuurnet.udb3-events.place-deleted+json',
            PlaceImportedFromUDB2::class => 'application/vnd.cultuurnet.udb3-events.place-imported-from-udb2-actor+json',
            PlaceProjectedToJSONLD::class => 'application/vnd.cultuurnet.udb3-events.place-projected-to-jsonld+json',
            PlaceUpdatedFromUDB2::class => 'application/vnd.cultuurnet.udb3-events.place-updated-from-udb2+json',
            TitleTranslated::class => 'application/vnd.cultuurnet.udb3-events.place-title-translated+json',
            TitleUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-title-updated+json',
            TypicalAgeRangeUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-typical-age-range-updated+json',
            TypicalAgeRangeDeleted::class => 'application/vnd.cultuurnet.udb3-events.place-typical-age-range-deleted+json',
            TypeUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-type-updated+json',
            ThemeUpdated::class => 'application/vnd.cultuurnet.udb3-events.place-theme-updated+json',
            // Moderation
            Published::class => 'application/vnd.cultuurnet.udb3-events.moderation.place-published+json',
            Approved::class => 'application/vnd.cultuurnet.udb3-events.moderation.place-approved+json',
            Rejected::class => 'application/vnd.cultuurnet.udb3-events.moderation.place-rejected+json',
            FlaggedAsDuplicate::class => 'application/vnd.cultuurnet.udb3-events.moderation.place-flagged-as-duplicate+json',
            FlaggedAsInappropriate::class => 'application/vnd.cultuurnet.udb3-events.moderation.place-flagged-as-inappropriate+json',
        ];
    }
}
