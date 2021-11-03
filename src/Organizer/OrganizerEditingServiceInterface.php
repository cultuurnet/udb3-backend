<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

interface OrganizerEditingServiceInterface
{
    public function create(
        Language $mainLanguage,
        Url $website,
        Title $title,
        ?Address $address = null,
        ?ContactPoint $contactPoint = null
    ): string;

    public function removeAddress(string $organizerId): void;

    public function updateContactPoint(string $organizerId, ContactPoint $contactPoint): void;

    public function delete(string $id): void;
}
