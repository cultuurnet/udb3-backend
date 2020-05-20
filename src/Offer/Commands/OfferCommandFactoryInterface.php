<?php

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractRemoveImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractApprove;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsDuplicate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsInappropriate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractReject;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use CultuurNet\UDB3\Language;

interface OfferCommandFactoryInterface
{
    /**
     * @param $id
     * @param Label $label
     * @return AbstractAddLabel
     */
    public function createAddLabelCommand($id, Label $label);

    /**
     * @param $id
     * @param Label $label
     * @return AbstractRemoveLabel
     */
    public function createRemoveLabelCommand($id, Label $label);

    /**
     * @param $id
     * @param UUID $imageId
     * @return AbstractAddImage
     */
    public function createAddImageCommand($id, UUID $imageId);

    /**
     * @param $id
     * @param Image $image
     * @return AbstractRemoveImage
     */
    public function createRemoveImageCommand($id, Image $image);

    /**
     * @param $id
     * @param UUID $mediaObjectId
     * @param StringLiteral $description
     * @param StringLiteral $copyrightHolder
     */
    public function createUpdateImageCommand(
        $id,
        UUID $mediaObjectId,
        StringLiteral $description,
        StringLiteral $copyrightHolder
    );

    /**
     * @param $id
     * @param Image $image
     * @return AbstractSelectMainImage
     */
    public function createSelectMainImageCommand($id, Image $image);

    /**
     * @param $id
     * @param Language $language
     * @param StringLiteral $title
     * @return AbstractUpdateTitle
     */
    public function createUpdateTitleCommand($id, Language $language, StringLiteral $title);

    /**
     * @param $id
     * @param Language $language
     * @param Description $description
     * @return AbstractUpdateDescription
     */
    public function createUpdateDescriptionCommand($id, Language $language, Description $description);

    /**
     * @param string $id
     * @param Calendar $calendar
     * @return AbstractUpdateCalendar
     */
    public function createUpdateCalendarCommand($id, Calendar $calendar);

    /**
     * @param string $id
     * @param AgeRange $ageRange
     * @return AbstractUpdateTypicalAgeRange
     */
    public function createUpdateTypicalAgeRangeCommand($id, AgeRange $ageRange);

    /**
     * @param string $id
     * @return AbstractDeleteTypicalAgeRange
     */
    public function createDeleteTypicalAgeRangeCommand($id);

    /**
     * @param string $id
     * @param string $organizerId
     * @return AbstractUpdateOrganizer
     */
    public function createUpdateOrganizerCommand($id, $organizerId);

    /**
     * @param string $id
     * @param string $organizerId
     * @return AbstractDeleteOrganizer
     */
    public function createDeleteOrganizerCommand($id, $organizerId);

    /**
     * @param string $id
     * @param ContactPoint $contactPoint
     * @return AbstractUpdateContactPoint
     */
    public function createUpdateContactPointCommand($id, ContactPoint $contactPoint);

    /**
     * @param string $id
     * @param BookingInfo $bookingInfo
     * @return AbstractUpdateBookingInfo
     */
    public function createUpdateBookingInfoCommand($id, BookingInfo $bookingInfo);

    /**
     * @param $id
     * @param PriceInfo $priceInfo
     * @return AbstractUpdatePriceInfo
     */
    public function createUpdatePriceInfoCommand($id, PriceInfo $priceInfo);

    /**
     * @param string $id
     * @return AbstractDeleteOffer
     */
    public function createDeleteOfferCommand($id);

    /**
     * @param string $id
     * @return AbstractApprove
     */
    public function createApproveCommand($id);

    /**
     * @param string $id
     * @param StringLiteral $reason
     * @return AbstractReject
     */
    public function createRejectCommand($id, StringLiteral $reason);

    /**
     * @param string $id
     * @return AbstractFlagAsInappropriate
     */
    public function createFlagAsInappropriate($id);

    /**
     * @param string $id
     * @return AbstractFlagAsDuplicate
     */
    public function createFlagAsDuplicate($id);

    /**
     * @param string $id
     * @param EventType $type
     * @return AbstractUpdateType
     */
    public function createUpdateTypeCommand($id, EventType $type);

    /**
     * @param string $id
     * @param Theme $theme
     * @return AbstractUpdateTheme
     */
    public function createUpdateThemeCommand($id, Theme $theme);

    /**
     * @param string $id
     * @param array $facilities
     * @return AbstractUpdateFacilities
     */
    public function createUpdateFacilitiesCommand($id, array $facilities);
}
