<?php

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Label;
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

    public function updateWebsite(string $organizerId, Url $website): void;

    public function updateTitle(string $organizerId, Title $title, Language $language): void;

    public function updateAddress(string $organizerId, Address $address, Language $language): void;

    public function updateContactPoint(string $organizerId, ContactPoint $contactPoint): void;

    public function addLabel(string $organizerId, Label $label): void;

    public function removeLabel(string $organizerId, Label $label): void;

    public function delete(string $id): void;
}
