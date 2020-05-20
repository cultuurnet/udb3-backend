<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface OfferEditingServiceInterface
{
    /**
     * @param $id
     * @param Label $label
     * @return string
     */
    public function addLabel($id, Label $label);

    /**
     * @param $id
     * @param Label $label
     * @return string
     */
    public function removeLabel($id, Label $label);

    /**
     * @param $id
     * @param Language $language
     * @param StringLiteral $title
     * @return string
     */
    public function updateTitle($id, Language $language, StringLiteral $title);

    /**
     * @param $id
     * @param Language $language
     * @param Description $description
     * @return string
     */
    public function updateDescription($id, Language $language, Description $description);

    /**
     * @param string $id
     * @param Calendar $calendar
     *
     * @return string
     */
    public function updateCalendar($id, Calendar $calendar);

    /**
     * @param string $id
     * @param UUID $imageId
     * @return string
     */
    public function addImage($id, UUID $imageId);

    /**
     * @param string $id
     * @param Image $image
     * @param StringLiteral $description
     * @param StringLiteral $copyrightHolder
     * @return string
     */
    public function updateImage($id, Image $image, StringLiteral $description, StringLiteral $copyrightHolder);

    /**
     * @param $id
     *  Id of the offer to remove the image from.
     *
     * @param Image $image
     *  The image that should be removed.
     *
     * @return string
     */
    public function removeImage($id, Image $image);

    /**
     * @param $id
     * @param Image $image
     * @return string
     */
    public function selectMainImage($id, Image $image);

    /**
     * @param string $id
     * @param AgeRange $ageRange
     * @return string
     */
    public function updateTypicalAgeRange($id, AgeRange $ageRange);

    /**
     * @param string $id
     * @return string
     */
    public function deleteTypicalAgeRange($id);

    /**
     * @param string $id
     * @param string $organizerId
     * @return string
     */
    public function updateOrganizer($id, $organizerId);

    /**
     * @param string $id
     * @param string $organizerId
     * @return string
     */
    public function deleteOrganizer($id, $organizerId);

    /**
     * @param string $id
     * @param ContactPoint $contactPoint
     * @return string
     */
    public function updateContactPoint($id, ContactPoint $contactPoint);

    /**
     * @param string $id
     * @param BookingInfo $bookingInfo
     * @return string
     */
    public function updateBookingInfo($id, BookingInfo $bookingInfo);

    /**
     * @param $id
     * @param PriceInfo $priceInfo
     */
    public function updatePriceInfo($id, PriceInfo $priceInfo);

    /**
     * @param string $id
     * @param StringLiteral $typeId
     * @return string
     */
    public function updateType($id, StringLiteral $typeId);

    /**
     * @param string $id
     * @param StringLiteral $themeId
     * @return string
     */
    public function updateTheme($id, StringLiteral $themeId);

    /**
     * @param string $id
     * @param array $facilities
     * @return string
     */
    public function updateFacilities($id, array $facilities);
}
