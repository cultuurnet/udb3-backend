<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use DateTimeImmutable;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
interface LegacyOffer
{
    public function getId(): string;

    public function getMainLanguage(): Language;

    public function getDescription(): ?Description;

    public function getType(): EventType;

    public function getTheme(): ?Theme;

    public function getCalendar(): Calendar;

    public function getOrganizerId(): ?string;

    public function getAgeRange(): ?AgeRange;

    public function getPriceInfo(): ?PriceInfo;

    public function getBookingInfo(): ?BookingInfo;

    public function getContactPoint(): ?ContactPoint;

    public function getAvailableFrom(\DateTimeImmutable $default): DateTimeImmutable;

    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations(): array;

    /**
     * @return Description[]
     *   Language code as key, and Description as value.
     */
    public function getDescriptionTranslations(): array;
}
