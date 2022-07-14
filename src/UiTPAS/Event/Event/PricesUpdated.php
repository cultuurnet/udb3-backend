<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\Model\ValueObject\Price\Tariffs;

final class PricesUpdated
{
    private string $id;

    private Tariffs $tariffs;

    public function __construct(
        string $id,
        Tariffs $tariffs
    ) {
        $this->id = $id;
        $this->tariffs = $tariffs;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTariffs(): Tariffs
    {
        return $this->tariffs;
    }
}
