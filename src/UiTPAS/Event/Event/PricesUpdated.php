<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;

final class PricesUpdated
{
    public function __construct(
        private readonly string $eventId,
        private readonly Tariffs $tariffs
    ) {
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getTariffs(): Tariffs
    {
        return $this->tariffs;
    }
}
