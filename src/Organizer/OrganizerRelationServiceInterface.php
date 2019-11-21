<?php

namespace CultuurNet\UDB3\Organizer;

interface OrganizerRelationServiceInterface
{
    /**
     * Deletes all relations to the to-be-deleted organizer.
     * The related entities can continue to exist, only the relationship is
     * deleted.
     *
     * @param string $organizerId
     */
    public function deleteOrganizer($organizerId);
}
