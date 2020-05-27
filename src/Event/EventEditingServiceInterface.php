<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\CalendarInterface;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

interface EventEditingServiceInterface
{

    /**
     * Update the title of an event.
     *
     * @param string $id
     * @param Language $language
     * @param StringLiteral $title
     */
    public function updateTitle($id, Language $language, StringLiteral $title);

    /**
     * Update the description of an event.
     *
     * @param string $id
     * @param Language $language
     * @param Description $description
     */
    public function updateDescription($id, Language $language, Description $description);

    /**
     * Update the typical age range of an event.
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
     * Update the organizer of an event.
     *
     * @param string $id
     * @param string $organizerId
     */
    public function updateOrganizer($id, $organizerId);

    /**
     * Update the organizer of an event.
     *
     * @param string $id
     * @param string $organizerId
     */
    public function deleteOrganizer($id, $organizerId);

    /**
     * Update the contact point of an event.
     *
     * @param string $id
     * @param ContactPoint $contactPoint
     */
    public function updateContactPoint($id, ContactPoint $contactPoint);

    /**
     * Add an image to the event.
     *
     * @param string $id
     * @param UUID $imageId
     */
    public function addImage($id, UUID $imageId);

    /**
     * Update an image of the event.
     *
     * @param $id
     * @param Image $image
     * @param \ValueObjects\StringLiteral\StringLiteral $description
     * @param \ValueObjects\StringLiteral\StringLiteral $copyrightHolder
     *
     * @return string
     *  The command id for this task.
     */
    public function updateImage(
        $id,
        Image $image,
        StringLiteral $description,
        StringLiteral $copyrightHolder
    );

    /**
     * Remove an image from an event.
     *
     * @param string $id
     * @param Image $image
     */
    public function removeImage($id, Image $image);

    /**
     * @param Language $mainLanguage
     * @param Title $title
     * @param EventType $eventType
     * @param LocationId $location
     * @param CalendarInterface $calendar
     * @param Theme/null $theme
     *
     * @return string $eventId
     */
    public function createEvent(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        CalendarInterface $calendar,
        $theme = null
    );

    /**
     * @param Language $mainLanguage
     * @param Title $title
     * @param EventType $eventType
     * @param LocationId $location
     * @param CalendarInterface $calendar
     * @param Theme|null $theme
     * @return string $eventId
     */
    public function createApprovedEvent(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        CalendarInterface $calendar,
        Theme $theme = null
    );

    /**
     * @param string $originalEventId
     * @param CalendarInterface $calendar
     * @return string $eventId
     *
     * @throws \InvalidArgumentException
     */
    public function copyEvent($originalEventId, CalendarInterface $calendar);

    /**
     * @param string $eventId
     * @param Title $title
     * @param EventType $eventType
     * @param LocationId $location
     * @param CalendarInterface $calendar
     * @param Theme/null $theme
     *
     * @return string $commandId
     */
    public function updateMajorInfo($eventId, Title $title, EventType $eventType, LocationId $location, CalendarInterface $calendar, $theme = null);

    /**
     * @param string $eventId
     * @param LocationId $locationId
     *
     * @return string $commandId
     */
    public function updateLocation($eventId, LocationId $locationId);

    /**
     * @param string $eventId
     * @param Audience $audience
     * @return string $commandId
     */
    public function updateAudience($eventId, Audience $audience);

    /**
     * @param string $eventId
     *
     * @return string $commandId
     */
    public function deleteEvent($eventId);
}
