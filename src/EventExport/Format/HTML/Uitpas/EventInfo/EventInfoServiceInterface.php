<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\EventInfo;

interface EventInfoServiceInterface
{
    public function getEventInfo(string $eventId): EventInfo;
}
