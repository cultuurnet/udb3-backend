<?php

namespace CultuurNet\UDB3\Offer\Events;

use \CultuurNet\UDB3\BookingInfo;

abstract class AbstractBookingInfoEvent extends AbstractEvent
{
    /**
     * @var BookingInfo
     */
    protected $bookingInfo;

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
        return parent::serialize() + array(
            'bookingInfo' => $this->bookingInfo->serialize(),
        );
    }

    public static function deserialize(array $data): AbstractBookingInfoEvent
    {
        return new static($data['item_id'], BookingInfo::deserialize($data['bookingInfo']));
    }
}
