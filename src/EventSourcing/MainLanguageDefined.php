<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

interface MainLanguageDefined
{
    public function getMainLanguage(): Language;
}
