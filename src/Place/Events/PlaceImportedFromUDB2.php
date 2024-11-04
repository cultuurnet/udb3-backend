<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;

final class PlaceImportedFromUDB2 extends ActorImportedFromUDB2 implements MainLanguageDefined, ConvertsToGranularEvents
{
    use PlaceFromUDB2;

    public function getMainLanguage(): Language
    {
        // Places imported from UDB2 always have main language NL.
        return new Language('nl');
    }
}
