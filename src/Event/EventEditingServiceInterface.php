<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\StringLiteral;

interface EventEditingServiceInterface
{
    public function deleteOrganizer(string $id, string $organizerId): void;
}
