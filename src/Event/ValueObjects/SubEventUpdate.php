<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate as Udb3ModelsSubEventUpdate;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;

final class SubEventUpdate
{
    private int $subEventId;

    private ?Status $status = null;

    private ?BookingAvailability $bookingAvailability = null;

    public function __construct(int $subEventId)
    {
        $this->subEventId = $subEventId;
    }

    public function withStatus(Status $status): SubEventUpdate
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withBookingAvailability(BookingAvailability $bookingAvailability): SubEventUpdate
    {
        $clone = clone $this;
        $clone->bookingAvailability = $bookingAvailability;
        return $clone;
    }

    public function getSubEventId(): int
    {
        return $this->subEventId;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): ?BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public static function fromUdb3ModelsSubEventUpdate(
        Udb3ModelsSubEventUpdate $udb3ModelsSubEventUpdate
    ): self {
        $update = new self($udb3ModelsSubEventUpdate->getSubEventId());

        $udb3ModelsStatus = $udb3ModelsSubEventUpdate->getStatus();
        if ($udb3ModelsStatus) {
            $update = $update->withStatus(
                Status::fromUdb3ModelStatus($udb3ModelsStatus)
            );
        }

        $udb3ModelsBookingAvailability = $udb3ModelsSubEventUpdate->getBookingAvailability();
        if ($udb3ModelsBookingAvailability) {
            $update = $update->withBookingAvailability(
                BookingAvailability::fromUdb3ModelBookingAvailability($udb3ModelsBookingAvailability)
            );
        }

        return $update;
    }
}
