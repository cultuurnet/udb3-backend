<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\BookingInfo;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use ValueObjects\StringLiteral\StringLiteral;

/**
 * @deprecated
 *   Refactor to implement RequestBodyParser and throw ApiProblemException
 */
class BookingInfoJSONDeserializer extends JSONDeserializer
{
    /**
     * @var DataValidatorInterface
     */
    private $validator;


    public function __construct(DataValidatorInterface $validator = null)
    {
        if (!$validator) {
            $validator = new BookingInfoDataValidator();
        }

        $this->validator = $validator;

        parent::__construct(true);
    }

    /**
     * @inheritdoc
     */
    public function deserialize(StringLiteral $data)
    {
        /* @var array $data */
        $data = parent::deserialize($data);

        $this->validator->validate($data);

        $bookingInfo = $data['bookingInfo'];

        $availabilityStarts = null;
        if (isset($bookingInfo['availabilityStarts'])) {
            $availabilityStarts = DateTimeFactory::fromISO8601($bookingInfo['availabilityStarts']);
        }

        $availabilityEnds = null;
        if (isset($bookingInfo['availabilityEnds'])) {
            $availabilityEnds = DateTimeFactory::fromISO8601($bookingInfo['availabilityEnds']);
        }

        $bookingInfo = new BookingInfo(
            isset($bookingInfo['url']) ? (string) $bookingInfo['url'] : null,
            isset($bookingInfo['urlLabel']) ? MultilingualString::deserialize($bookingInfo['urlLabel']) : null,
            isset($bookingInfo['phone']) ? (string) $bookingInfo['phone'] : null,
            isset($bookingInfo['email']) ? (string) $bookingInfo['email'] : null,
            $availabilityStarts,
            $availabilityEnds
        );

        return $bookingInfo;
    }
}
