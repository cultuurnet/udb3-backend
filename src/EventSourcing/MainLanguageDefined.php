<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

use CultuurNet\UDB3\Language;

interface MainLanguageDefined
{
    public function getMainLanguage(): Language;
}
