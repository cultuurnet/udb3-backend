<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

final class Timestamp implements Serializable
{
    private DateTimeInterface $startDate;

    private DateTimeInterface $endDate;

    private Status $status;

    private BookingAvailability $bookingAvailability;

    public function __construct(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        Status $status = null,
        BookingAvailability $bookingAvailability = null
    ) {
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('End date can not be earlier than start date.');
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status ?? new Status(StatusType::available(), []);
        $this->bookingAvailability = $bookingAvailability ?? BookingAvailability::available();
    }

    public function withStatus(Status $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
        return $clone;
    }

    public function withBookingAvailability(BookingAvailability $bookingAvailability): self
    {
        $clone = clone $this;
        $clone->bookingAvailability = $bookingAvailability;
        return $clone;
    }

    public function getStartDate(): DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): DateTimeInterface
    {
        return $this->endDate;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getBookingAvailability(): BookingAvailability
    {
        return $this->bookingAvailability;
    }

    public static function deserialize(array $data): Timestamp
    {
        $status = null;
        if (isset($data['status'])) {
            $status = Status::deserialize($data['status']);
        }

        $bookingAvailability = null;
        if (isset($data['bookingAvailability'])) {
            $bookingAvailability = BookingAvailability::deserialize($data['bookingAvailability']);
        }

        $startDate = DateTime::createFromFormat(DateTimeInterface::ATOM, $data['startDate']);
        $endDate = DateTime::createFromFormat(DateTimeInterface::ATOM, $data['endDate']);

        if ($startDate > $endDate) {
            $endDate = $startDate;
        }

        return new self($startDate, $endDate, $status, $bookingAvailability);
    }

    public function serialize(): array
    {
        return [
            'startDate' => $this->startDate->format(DateTimeInterface::ATOM),
            'endDate' => $this->endDate->format(DateTimeInterface::ATOM),
            'status' => $this->status->serialize(),
            'bookingAvailability' => $this->bookingAvailability->serialize(),
        ];
    }

    public function toJsonLd(): array
    {
        $jsonLd = $this->serialize();
        $jsonLd['@type'] = 'Event';

        return $jsonLd;
    }

    public static function fromUdb3ModelSubEvent(SubEvent $subEvent): Timestamp
    {
        return new Timestamp(
            $subEvent->getDateRange()->getFrom(),
            $subEvent->getDateRange()->getTo(),
            Status::fromUdb3ModelStatus($subEvent->getStatus()),
            BookingAvailability::fromUdb3ModelBookingAvailability($subEvent->getBookingAvailability())
        );
    }
}
