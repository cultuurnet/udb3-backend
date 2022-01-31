<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Title;

interface LegacyOrganizer
{
    public function getId(): string;

    public function getMainLanguage(): Language;

    public function getTitle(): Title;

    public function getWebsite(): Url;

    public function getAddress(): ?Address;

    public function getContactPoint(): ?ContactPoint;

    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations(): array;

    /**
     * @return Address[]
     *   Language code as key, and Address as value.
     */
    public function getAddressTranslations(): array;
}
