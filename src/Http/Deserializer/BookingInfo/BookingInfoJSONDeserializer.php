<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\BookingInfo;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\ValueObject\MultilingualString;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class BookingInfoJSONDeserializer extends JSONDeserializer
{
    public function __construct()
    {
        parent::__construct(true);
    }

    public function deserialize(string $data): BookingInfo
    {
        /* @var array $data */
        $data = parent::deserialize($data);

        $bookingInfo = $data['bookingInfo'];

        $availabilityStarts = null;
        if (isset($bookingInfo['availabilityStarts'])) {
            $availabilityStarts = DateTimeFactory::fromISO8601($bookingInfo['availabilityStarts']);
        }

        $availabilityEnds = null;
        if (isset($bookingInfo['availabilityEnds'])) {
            $availabilityEnds = DateTimeFactory::fromISO8601($bookingInfo['availabilityEnds']);
        }

        return new BookingInfo(
            isset($bookingInfo['url']) ? (string) $bookingInfo['url'] : null,
            isset($bookingInfo['urlLabel']) ? MultilingualString::deserialize($bookingInfo['urlLabel']) : null,
            isset($bookingInfo['phone']) ? (string) $bookingInfo['phone'] : null,
            isset($bookingInfo['email']) ? (string) $bookingInfo['email'] : null,
            $availabilityStarts,
            $availabilityEnds
        );
    }
}
