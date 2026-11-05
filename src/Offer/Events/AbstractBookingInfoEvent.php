<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Contact\BookingInfoNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidEmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\InvalidUrl;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;

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
            (new BookingInfoDenormalizer())->denormalize(
                self::withoutInvalidEmailAndUrl($data['bookingInfo']),
                BookingInfo::class
            )
        );
    }

    /**
     * The event store can hold an email or url that is no longer valid, so leave those out instead of failing.
     */
    private static function withoutInvalidEmailAndUrl(array $bookingInfo): array
    {
        if (!empty($bookingInfo['email'])) {
            try {
                new EmailAddress($bookingInfo['email']);
            } catch (InvalidEmailAddress) {
                unset($bookingInfo['email']);
            }
        }

        if (!empty($bookingInfo['url'])) {
            try {
                new Url($bookingInfo['url']);
            } catch (InvalidUrl) {
                unset($bookingInfo['url'], $bookingInfo['urlLabel']);
            }
        }

        return $bookingInfo;
    }
}
