<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use ValueObjects\StringLiteral\StringLiteral;

interface EventEditingServiceInterface
{
    /**
     * Update the title of an event.
     *
     * @param string $id
     */
    public function updateTitle($id, Language $language, StringLiteral $title);

    /**
     * Update the description of an event.
     *
     * @param string $id
     */
    public function updateDescription($id, Language $language, Description $description);

    /**
     * Update the typical age range of an event.
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
     */
    public function updateContactPoint($id, ContactPoint $contactPoint);

    /**
     * Add an image to the event.
     *
     * @param string $id
     */
    public function addImage($id, UUID $imageId);

    /**
     * Remove an image from an event.
     *
     * @param string $id
     */
    public function removeImage($id, Image $image);

    /**
     * @param Theme|null $theme
     *
     * @return string $eventId
     */
    public function createEvent(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        $theme = null
    );

    /**
     * @return string $eventId
     */
    public function createApprovedEvent(
        Language $mainLanguage,
        Title $title,
        EventType $eventType,
        LocationId $location,
        Calendar $calendar,
        Theme $theme = null
    );
}
