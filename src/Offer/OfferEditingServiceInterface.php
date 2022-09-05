<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

interface OfferEditingServiceInterface
{
    public function deleteOrganizer(string $id, string $organizerId): void;
}
