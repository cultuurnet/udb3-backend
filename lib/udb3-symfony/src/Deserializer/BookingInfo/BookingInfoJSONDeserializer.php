<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\BookingInfo;

use CultuurNet\Deserializer\JSONDeserializer;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use ValueObjects\StringLiteral\StringLiteral;

class BookingInfoJSONDeserializer extends JSONDeserializer
{
    /**
     * @var DataValidatorInterface
     */
    private $validator;

    /**
     * @param DataValidatorInterface|null $validator
     */
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
            $availabilityStarts = \DateTimeImmutable::createFromFormat(
                \DATE_ATOM,
                $bookingInfo['availabilityStarts']
            );
        }

        $availabilityEnds = null;
        if (isset($bookingInfo['availabilityEnds'])) {
            $availabilityEnds = \DateTimeImmutable::createFromFormat(
                \DATE_ATOM,
                $bookingInfo['availabilityEnds']
            );
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
