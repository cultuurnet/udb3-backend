<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use DateTimeImmutable;

final class UpdateTypicalBirthDate extends AbstractCommand
{
    private DateTimeImmutable $typicalBirthDate;

    public function __construct(string $itemId, DateTimeImmutable $typicalBirthDate)
    {
        parent::__construct($itemId);
        $this->typicalBirthDate = $typicalBirthDate;
    }

    public function getTypicalBirthDate(): DateTimeImmutable
    {
        return $this->typicalBirthDate;
    }
}
