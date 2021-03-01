<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface PlaceEditingServiceInterface
{
    /**
     * Create a new place.
     *
     *
     * @return string $eventId
     */
    public function createPlace(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        Address $address,
        Calendar $calendar,
        Theme $theme = null
    );

    /**
     * @return string $eventId
     */
    public function createApprovedPlace(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        Address $address,
        Calendar $calendar,
        Theme $theme = null
    );

    /**
     * @param string $id
     *
     * @return string $id
     */
    public function deletePlace($id);

    /**
     * Update the major info of a place.
     *
     * @param string $id
     */
    public function updateMajorInfo($id, Title $title, EventType $eventType, Address $address, Calendar $calendar, Theme $theme = null);

    /**
     * @param string $id
     */
    public function updateAddress($id, Address $address, Language $language);

    /**
     * Update the description of a place.
     *
     * @param string $id
     */
    public function updateDescription($id, Language $language, Description $description);

    /**
     * Update the typical age range of a place.
     *
     * @param string $id
     */
    public function updateTypicalAgeRange($id, AgeRange $ageRange);

    /**
     * Delete the typical age range of a place.
     *
     * @param string $id
     */
    public function deleteTypicalAgeRange($id);

    /**
     * Update the organizer of a place.
     *
     * @param string $id
     * @param string $organizerId
     */
    public function updateOrganizer($id, $organizerId);

    /**
     * Update the organizer of a place.
     *
     * @param string $id
     * @param string $organizerId
     */
    public function deleteOrganizer($id, $organizerId);

    /**
     * Update the contact info of a place.
     *
     * @param string $id
     */
    public function updateContactPoint($id, ContactPoint $contactPoint);

    /**
     * Add an image to the place.
     *
     * @param string $id
     */
    public function addImage($id, UUID $imageId);

    /**
     * Update an image of the place.
     *
     * @param string $id
     */
    public function updateImage($id, Image $image, StringLiteral $description, StringLiteral $copyrightHolder);

    /**
     * Remove an image from the place.
     *
     * @param string $id
     */
    public function removeImage($id, Image $image);
}
