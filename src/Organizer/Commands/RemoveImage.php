<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class RemoveImage
{
    private string $organizerId;

    private UUID $imageId;

    public function __construct(string $organizerId, UUID $imageId)
    {
        $this->organizerId = $organizerId;
        $this->imageId = $imageId;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function getImageId(): UUID
    {
        return $this->imageId;
    }
}
