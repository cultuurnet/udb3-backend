<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

interface OrganizerRelationServiceInterface
{
    /**
     * Deletes all relations to the to-be-deleted organizer.
     * The related entities can continue to exist, only the relationship is
     * deleted.
     */
    public function deleteOrganizer(string $organizerId): void;
}
