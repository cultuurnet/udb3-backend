<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractAddImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractRemoveImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractSelectMainImage;
use CultuurNet\UDB3\Offer\Commands\Image\AbstractUpdateImage;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractApprove;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsDuplicate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractFlagAsInappropriate;
use CultuurNet\UDB3\Offer\Commands\Moderation\AbstractReject;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;
use CultuurNet\UDB3\Language;

interface OfferCommandFactoryInterface
{
    public function createAddImageCommand(string $id, UUID $imageId): AbstractAddImage;

    public function createRemoveImageCommand(string $id, Image $image): AbstractRemoveImage;

    public function createUpdateImageCommand(string $id, UUID $mediaObjectId, StringLiteral $description, CopyrightHolder $copyrightHolder): AbstractUpdateImage;

    public function createSelectMainImageCommand(string $id, Image $image): AbstractSelectMainImage;

    public function createUpdateTitleCommand(string $id, Language $language, StringLiteral $title): AbstractUpdateTitle;

    public function createUpdateDescriptionCommand(string $id, Language $language, Description $description): AbstractUpdateDescription;

    public function createUpdateCalendarCommand(string $id, Calendar $calendar): AbstractUpdateCalendar;

    public function createUpdateTypicalAgeRangeCommand(string $id, AgeRange $ageRange): AbstractUpdateTypicalAgeRange;

    public function createDeleteTypicalAgeRangeCommand(string $id): AbstractDeleteTypicalAgeRange;

    public function createUpdateOrganizerCommand(string $id, string $organizerId): AbstractUpdateOrganizer;

    public function createDeleteOrganizerCommand(string $id, string $organizerId): AbstractDeleteOrganizer;

    public function createUpdateContactPointCommand(string $id, ContactPoint $contactPoint): AbstractUpdateContactPoint;

    public function createUpdateBookingInfoCommand(string $id, BookingInfo $bookingInfo): AbstractUpdateBookingInfo;

    public function createUpdatePriceInfoCommand(string $id, PriceInfo $priceInfo): AbstractUpdatePriceInfo;

    public function createDeleteOfferCommand(string $id): AbstractDeleteOffer;

    public function createApproveCommand(string $id): AbstractApprove;

    public function createRejectCommand(string $id, StringLiteral $reason): AbstractReject;

    public function createFlagAsInappropriate(string $id): AbstractFlagAsInappropriate;

    public function createFlagAsDuplicate(string $id): AbstractFlagAsDuplicate;

    public function createUpdateTypeCommand(string $id, EventType $type): AbstractUpdateType;

    public function createUpdateThemeCommand(string $id, Theme $theme): AbstractUpdateTheme;

    public function createUpdateFacilitiesCommand(string $id, array $facilities): AbstractUpdateFacilities;
}
