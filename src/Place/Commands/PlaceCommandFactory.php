<?php

namespace CultuurNet\UDB3\Place\Commands;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOffer;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteOrganizer;
use CultuurNet\UDB3\Offer\Commands\AbstractDeleteTypicalAgeRange;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateCalendar;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateFacilities;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdatePriceInfo;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTheme;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTitle;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateBookingInfo;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateContactPoint;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateDescription;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateOrganizer;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateType;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateTypicalAgeRange;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractRemoveImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractUpdateImage;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractApprove;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsDuplicate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsInappropriate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractReject;
use CultuurNet\UDB3\Offer\Commands\OfferCommandFactoryInterface;
use CultuurNet\UDB3\Place\Commands\Moderation\Approve;
use CultuurNet\UDB3\Place\Commands\Moderation\FlagAsDuplicate;
use CultuurNet\UDB3\Place\Commands\Moderation\FlagAsInappropriate;
use CultuurNet\UDB3\Place\Commands\Moderation\Reject;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class PlaceCommandFactory implements OfferCommandFactoryInterface
{
    public function createAddLabelCommand(string $id, Label $label): AddLabel
    {
        return new AddLabel($id, $label);
    }

    public function createRemoveLabelCommand(string $id, Label $label): RemoveLabel
    {
        return new RemoveLabel($id, $label);
    }

    public function createAddImageCommand(string $id, UUID $imageId): AbstractAddImage
    {
        return new AddImage($id, $imageId);
    }

    public function createRemoveImageCommand(string $id, Image $image): AbstractRemoveImage
    {
        return new RemoveImage($id, $image);
    }

    public function createUpdateImageCommand(
        string $id,
        UUID $mediaObjectId,
        StringLiteral $description,
        StringLiteral $copyrightHolder
    ): AbstractUpdateImage {
        return new UpdateImage(
            $id,
            $mediaObjectId,
            $description,
            $copyrightHolder
        );
    }

    public function createSelectMainImage(string $id, Image $image): AbstractSelectMainImage
    {
        return new SelectMainImage($id, $image);
    }

    public function createUpdateTitleCommand(string $id, Language $language, StringLiteral $title): AbstractUpdateTitle
    {
        return new UpdateTitle($id, $language, $title);
    }

    public function createUpdateDescriptionCommand(string $id, Language $language, Description $description): AbstractUpdateDescription
    {
        return new UpdateDescription($id, $language, $description);
    }

    public function createUpdateCalendarCommand(string $id, Calendar $calendar): AbstractUpdateCalendar
    {
        return new UpdateCalendar($id, $calendar);
    }

    public function createSelectMainImageCommand(string $id, Image $image): AbstractSelectMainImage
    {
        return new SelectMainImage($id, $image);
    }

    public function createUpdateTypicalAgeRangeCommand(string $id, AgeRange $ageRange): AbstractUpdateTypicalAgeRange
    {
        return new UpdateTypicalAgeRange($id, $ageRange);
    }

    public function createDeleteTypicalAgeRangeCommand(string $id): AbstractDeleteTypicalAgeRange
    {
        return new DeleteTypicalAgeRange($id);
    }

    public function createUpdateOrganizerCommand(string $id, string $organizerId): AbstractUpdateOrganizer
    {
        return new UpdateOrganizer($id, $organizerId);
    }

    public function createDeleteOrganizerCommand(string $id, string $organizerId): AbstractDeleteOrganizer
    {
        return new DeleteOrganizer($id, $organizerId);
    }

    public function createUpdateContactPointCommand(string $id, ContactPoint $contactPoint): AbstractUpdateContactPoint
    {
        return new UpdateContactPoint($id, $contactPoint);
    }

    public function createUpdateBookingInfoCommand(string $id, BookingInfo $bookingInfo): AbstractUpdateBookingInfo
    {
        return new UpdateBookingInfo($id, $bookingInfo);
    }

    public function createUpdatePriceInfoCommand(string $id, PriceInfo $priceInfo): AbstractUpdatePriceInfo
    {
        return new UpdatePriceInfo($id, $priceInfo);
    }

    public function createDeleteOfferCommand(string $id): AbstractDeleteOffer
    {
        return new DeletePlace($id);
    }

    public function createApproveCommand(string $id): AbstractApprove
    {
        return new Approve($id);
    }

    public function createRejectCommand(string $id, StringLiteral $reason): AbstractReject
    {
        return new Reject($id, $reason);
    }

    public function createFlagAsInappropriate(string $id): AbstractFlagAsInappropriate
    {
        return new FlagAsInappropriate($id);
    }

    public function createFlagAsDuplicate(string $id): AbstractFlagAsDuplicate
    {
        return new FlagAsDuplicate($id);
    }

    public function createUpdateTypeCommand(string $id, EventType $type): AbstractUpdateType
    {
        return new UpdateType($id, $type);
    }

    public function createUpdateThemeCommand(string $id, Theme $theme): AbstractUpdateTheme
    {
        return new UpdateTheme($id, $theme);
    }

    public function createUpdateFacilitiesCommand(string $id, array $facilities): AbstractUpdateFacilities
    {
        return new UpdateFacilities($id, $facilities);
    }
}
