<?php

namespace CultuurNet\UDB3;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

final class Timestamp implements SerializableInterface
{
    /**
     * @var DateTimeInterface
     */
    private $startDate;

    /**
     * @var DateTimeInterface
     */
    private $endDate;

    /**
     * @var Status
     */
    private $status;

    public function __construct(
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
        Status $status = null
    ) {
        if ($endDate < $startDate) {
            throw new InvalidArgumentException('End date can not be earlier than start date.');
        }

        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->status = $status ?? new Status(StatusType::available(), []);
    }

    public function withStatus(Status $status): self
    {
        $clone = clone $this;
        $clone->status = $status;
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

    public static function deserialize(array $data): Timestamp
    {
        $status = null;
        if (isset($data['status'])) {
            $status = Status::deserialize($data['status']);
        }

        return new static(
            DateTime::createFromFormat(DateTime::ATOM, $data['startDate']),
            DateTime::createFromFormat(DateTime::ATOM, $data['endDate']),
            $status
        );
    }

    public function serialize(): array
    {
        return [
            'startDate' => $this->startDate->format(DateTime::ATOM),
            'endDate' => $this->endDate->format(DateTime::ATOM),
            'status' => $this->status->serialize(),
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
            Status::fromUdb3ModelStatus($subEvent->getStatus())
        );
    }
}
