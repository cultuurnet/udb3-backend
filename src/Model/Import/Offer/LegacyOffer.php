<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Offer;

use CultuurNet\UDB3\Model\ValueObject\Text\Title;
use DateTimeImmutable;

/**
 * @deprecated Should no longer be used because all commands should use the VOs from the Model namespace.
 */
interface LegacyOffer
{
    public function getOrganizerId(): ?string;

    public function getAvailableFrom(\DateTimeImmutable $default): DateTimeImmutable;

    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations(): array;
}
