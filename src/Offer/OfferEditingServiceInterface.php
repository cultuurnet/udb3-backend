<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\StringLiteral;

interface OfferEditingServiceInterface
{
    public function updateTitle(string $id, Language $language, StringLiteral $title): void;
    public function updateDescription(string $id, Language $language, Description $description): void;
    public function addImage(string $id, UUID $imageId): void;
    public function updateImage(string $id, Image $image, StringLiteral $description, CopyrightHolder $copyrightHolder): void;
    public function removeImage(string $id, Image $image): void;
    public function selectMainImage(string $id, Image $image): void;
    public function updateTypicalAgeRange(string $id, AgeRange $ageRange): void;
    public function deleteTypicalAgeRange(string $id): void;
    public function deleteOrganizer(string $id, string $organizerId): void;
    public function updateContactPoint(string $id, ContactPoint $contactPoint): void;
    public function updateBookingInfo(string $id, BookingInfo $bookingInfo): void;
}
