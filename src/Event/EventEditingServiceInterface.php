<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\StringLiteral;

interface EventEditingServiceInterface
{
    public function updateTitle(string $id, Language $language, StringLiteral $title): void;
    public function updateDescription(string $id, Language $language, Description $description): void;
    public function updateTypicalAgeRange(string $id, AgeRange $ageRange): void;
    public function deleteTypicalAgeRange(string $id): void;
    public function updateOrganizer(string $id, string $organizerId): void;
    public function deleteOrganizer(string $id, string $organizerId): void;
    public function updateContactPoint(string $id, ContactPoint $contactPoint): void;
    public function addImage(string $id, UUID $imageId): void;
    public function removeImage(string $id, Image $image): void;
}
