<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Organizer;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;
use ValueObjects\Web\Url;

interface LegacyOrganizer
{
    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations(): array;
}
