<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Organizer;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Title;

interface LegacyOrganizer
{
    /**
     * @return Title[]
     *   Language code as key, and Title as value.
     */
    public function getTitleTranslations(): array;
}
