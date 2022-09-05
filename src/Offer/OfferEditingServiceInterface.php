<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\StringLiteral;

interface OfferEditingServiceInterface
{
    public function updateTitle(string $id, Language $language, StringLiteral $title): void;
    public function updateDescription(string $id, Language $language, Description $description): void;
    public function deleteOrganizer(string $id, string $organizerId): void;
}
