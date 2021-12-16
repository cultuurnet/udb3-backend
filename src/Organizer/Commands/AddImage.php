<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Commands;

use CultuurNet\UDB3\Model\ValueObject\MediaObject\Image;

final class AddImage
{
    private string $organizerId;

    private Image $image;

    public function __construct(string $organizerId, Image $image)
    {
        $this->organizerId = $organizerId;
        $this->image = $image;
    }

    public function getOrganizerId(): string
    {
        return $this->organizerId;
    }

    public function getImage(): Image
    {
        return $this->image;
    }
}
