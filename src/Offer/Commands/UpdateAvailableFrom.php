<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use DateTimeInterface;

final class UpdateAvailableFrom extends AbstractCommand
{
    private DateTimeInterface $availableFrom;

    public function __construct(string $itemId, DateTimeInterface $availableFrom)
    {
        parent::__construct($itemId);

        $this->availableFrom = $availableFrom;
    }

    public function getAvailableFrom(): DateTimeInterface
    {
        return $this->availableFrom;
    }
}
