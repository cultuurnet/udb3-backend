<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use DateTimeImmutable;

final class TypicalBirthDateUpdated extends AbstractEvent
{
    private DateTimeImmutable $typicalBirthDate;

    public function __construct(string $itemId, DateTimeImmutable $typicalBirthDate)
    {
        parent::__construct($itemId);
        $this->typicalBirthDate = $typicalBirthDate->setTime(0, 0);
    }

    public function getTypicalBirthDate(): DateTimeImmutable
    {
        return $this->typicalBirthDate;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'typicalBirthDate' => $this->typicalBirthDate->format('Y-m-d'),
        ];
    }

    public static function deserialize(array $data): TypicalBirthDateUpdated
    {
        return new self(
            $data['item_id'],
            new DateTimeImmutable($data['typicalBirthDate'])
        );
    }
}
