<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Actor\ActorImportedFromUDB2;
use CultuurNet\UDB3\EventSourcing\ConvertsToGranularEvents;

final class PlaceUpdatedFromUDB2 extends ActorImportedFromUDB2 implements ConvertsToGranularEvents
{
    use PlaceFromUDB2;
}
