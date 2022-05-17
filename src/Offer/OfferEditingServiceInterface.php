<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\StringLiteral;

interface OfferEditingServiceInterface
{
    /**
     * @param string $id
     * @return string
     */
    public function updateTitle($id, Language $language, StringLiteral $title);

    /**
     * @param string $id
     * @return string
     */
    public function updateDescription($id, Language $language, Description $description);

    /**
     * @param string $id
     * @return string
     */
    public function addImage($id, UUID $imageId);

    public function updateImage($id, Image $image, StringLiteral $description, CopyrightHolder $copyrightHolder): void;

    /**
     * @param string $id
     *  Id of the offer to remove the image from.
     *
     * @param Image $image
     *  The image that should be removed.
     *
     * @return string
     */
    public function removeImage($id, Image $image);

    /**
     * @param string $id
     * @return string
     */
    public function selectMainImage($id, Image $image);

    /**
     * @param string $id
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
     * @return string
     */
    public function updateContactPoint($id, ContactPoint $contactPoint);

    /**
     * @param string $id
     * @return string
     */
    public function updateBookingInfo($id, BookingInfo $bookingInfo);
}
