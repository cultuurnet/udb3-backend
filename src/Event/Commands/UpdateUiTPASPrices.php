<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;

final class UpdateUiTPASPrices
{
    private string $eventId;

    private Tariffs $tariffs;

    public function __construct(string $eventId, Tariffs $tariffs)
    {
        $this->eventId = $eventId;
        $this->tariffs = $tariffs;
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
