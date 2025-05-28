<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Item\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\Events\LabelsReplaced;
use CultuurNet\UDB3\Offer\Item\Events\AvailableFromUpdated;
use CultuurNet\UDB3\Offer\Item\Events\BookingInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\CalendarUpdated;
use CultuurNet\UDB3\Offer\Item\Events\ContactPointUpdated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionDeleted;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionTranslated;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionUpdated;
use CultuurNet\UDB3\Offer\Item\Events\FacilitiesUpdated;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesImportedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\Image\ImagesUpdatedFromUDB2;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use CultuurNet\UDB3\Offer\Item\Events\LabelRemoved;
use CultuurNet\UDB3\Offer\Item\Events\LabelsImported;
use CultuurNet\UDB3\Offer\Item\Events\MainImageSelected;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Approved;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Published;
use CultuurNet\UDB3\Offer\Item\Events\Moderation\Rejected;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerDeleted;
use CultuurNet\UDB3\Offer\Item\Events\OrganizerUpdated;
use CultuurNet\UDB3\Offer\Item\Events\PriceInfoUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TitleTranslated;
use CultuurNet\UDB3\Offer\Item\Events\TitleUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeDeleted;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeUpdated;
use CultuurNet\UDB3\Offer\Item\Events\VideoAdded;
use CultuurNet\UDB3\Offer\Item\Events\VideoDeleted;
use CultuurNet\UDB3\Offer\Item\Events\VideoUpdated;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\OfferLDProjector;
use CultuurNet\UDB3\Offer\Item\Events\ImageAdded;
use CultuurNet\UDB3\Offer\Item\Events\ImageRemoved;
use CultuurNet\UDB3\Offer\Item\Events\ImageUpdated;

class ItemLDProjector extends OfferLDProjector
{
    protected function getLabelAddedClassName(): string
    {
        return LabelAdded::class;
    }

    protected function getLabelRemovedClassName(): string
    {
        return LabelRemoved::class;
    }

    protected function getImageAddedClassName(): string
    {
        return ImageAdded::class;
    }

    protected function getLabelsImportedClassName(): string
    {
        return LabelsImported::class;
    }

    protected function getLabelsReplacedClassName(): string
    {
        return LabelsReplaced::class;
    }

    protected function getImageRemovedClassName(): string
    {
        return ImageRemoved::class;
    }

    protected function getImageUpdatedClassName(): string
    {
        return ImageUpdated::class;
    }

    protected function getMainImageSelectedClassName(): string
    {
        return MainImageSelected::class;
    }

    protected function getVideoAddedClassName(): string
    {
        return VideoAdded::class;
    }

    protected function getVideoDeletedClassName(): string
    {
        return VideoDeleted::class;
    }

    protected function getVideoUpdatedClassName(): string
    {
        return VideoUpdated::class;
    }

    protected function getTitleTranslatedClassName(): string
    {
        return TitleTranslated::class;
    }

    protected function getDescriptionTranslatedClassName(): string
    {
        return DescriptionTranslated::class;
    }

    protected function getOrganizerUpdatedClassName(): string
    {
        return OrganizerUpdated::class;
    }

    protected function getOrganizerDeletedClassName(): string
    {
        return OrganizerDeleted::class;
    }

    protected function getBookingInfoUpdatedClassName(): string
    {
        return BookingInfoUpdated::class;
    }

    protected function getPriceInfoUpdatedClassName(): string
    {
        return PriceInfoUpdated::class;
    }

    protected function getContactPointUpdatedClassName(): string
    {
        return ContactPointUpdated::class;
    }

    protected function getDescriptionUpdatedClassName(): string
    {
        return DescriptionUpdated::class;
    }

    protected function getDescriptionDeletedClassName(): string
    {
        return DescriptionDeleted::class;
    }

    protected function getCalendarUpdatedClassName(): string
    {
        return CalendarUpdated::class;
    }

    protected function getTypicalAgeRangeUpdatedClassName(): string
    {
        return TypicalAgeRangeUpdated::class;
    }

    protected function getTypicalAgeRangeDeletedClassName(): string
    {
        return TypicalAgeRangeDeleted::class;
    }

    protected function getAvailableFromUpdatedClassName(): string
    {
        return AvailableFromUpdated::class;
    }

    protected function getPublishedClassName(): string
    {
        return Published::class;
    }

    protected function getApprovedClassName(): string
    {
        return Approved::class;
    }

    protected function getRejectedClassName(): string
    {
        return Rejected::class;
    }

    protected function getFlaggedAsDuplicateClassName(): string
    {
        return FlaggedAsDuplicate::class;
    }

    protected function getFlaggedAsInappropriateClassName(): string
    {
        return FlaggedAsInappropriate::class;
    }

    protected function getImagesImportedFromUdb2ClassName(): string
    {
        return ImagesImportedFromUDB2::class;
    }

    protected function getImagesUpdatedFromUdb2ClassName(): string
    {
        return ImagesUpdatedFromUDB2::class;
    }

    protected function getTitleUpdatedClassName(): string
    {
        return TitleUpdated::class;
    }

    protected function getTypeUpdatedClassName(): string
    {
        return TypeUpdated::class;
    }

    protected function getFacilitiesUpdatedClassName(): string
    {
        return FacilitiesUpdated::class;
    }
}
