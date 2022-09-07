<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

interface EventEditingServiceInterface
{
    public function deleteOrganizer(string $id, string $organizerId): void;
}
