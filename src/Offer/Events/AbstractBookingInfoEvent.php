<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;

abstract class AbstractBookingInfoEvent extends AbstractEvent
{
    protected BookingInfo $bookingInfo;

    final public function __construct(string $id, BookingInfo $bookingInfo)
    {
        parent::__construct($id);
        $this->bookingInfo = $bookingInfo;
    }

    public function getBookingInfo(): BookingInfo
    {
        return $this->bookingInfo;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
            'bookingInfo' => (new BookingInfoNormalizer())->normalize($this->bookingInfo),
        ];
    }

    public static function deserialize(array $data): AbstractBookingInfoEvent
    {
        return new static(
            $data['item_id'],
            (new BookingInfoDenormalizer())->denormalize($data['bookingInfo'], BookingInfo::class)
        );
    }
}
