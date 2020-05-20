<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\CalendarInterface;
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
     * @param Language $mainLanguage
     * @param Title $title
     * @param EventType $eventType
     * @param Address $address
     * @param CalendarInterface $calendar
     * @param Theme|null $theme
     *
     * @return string $eventId
     */
    public function createPlace(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        Address $address,
        CalendarInterface $calendar,
        Theme $theme = null
    );

    /**
     * @param Language $mainLanguage
     * @param Title $title
     * @param EventType $eventType
     * @param Address $address
     * @param CalendarInterface $calendar
     * @param Theme|null $theme
     * @return string $eventId
     */
    public function createApprovedPlace(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        Address $address,
        CalendarInterface $calendar,
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
     * @param Title $title
     * @param EventType $eventType
     * @param \CultuurNet\UDB3\Address\Address $address
     * @param CalendarInterface $calendar
     * @param Theme|null $theme
     */
    public function updateMajorInfo($id, Title $title, EventType $eventType, Address $address, CalendarInterface $calendar, Theme $theme = null);

    /**
     * @param string $id
     * @param Address $address
     * @param Language $language
     */
    public function updateAddress($id, Address $address, Language $language);

    /**
     * Update the description of a place.
     *
     * @param string $id
     * @param Language $language
     * @param Description $description
     */
    public function updateDescription($id, Language $language, Description $description);

    /**
     * Update the typical age range of a place.
     *
     * @param string $id
     * @param AgeRange $ageRange
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
     * @param ContactPoint $contactPoint
     */
    public function updateContactPoint($id, ContactPoint $contactPoint);

    /**
     * Add an image to the place.
     *
     * @param string $id
     * @param UUID $imageId
     */
    public function addImage($id, UUID $imageId);

    /**
     * Update an image of the place.
     *
     * @param $id
     * @param Image $image
     * @param \ValueObjects\StringLiteral\StringLiteral $description
     * @param \ValueObjects\StringLiteral\StringLiteral $copyrightHolder
     */
    public function updateImage($id, Image $image, StringLiteral $description, StringLiteral $copyrightHolder);

    /**
     * Remove an image from the place.
     *
     * @param string $id
     * @param Image $image
     */
    public function removeImage($id, Image $image);
}
