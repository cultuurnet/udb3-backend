<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\BookingInfo;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\ValueObject\MultilingualString;

class BookingInfoDataValidator implements DataValidatorInterface
{
    public function validate(array $data): void
    {
        if (!isset($data['bookingInfo'])) {
            $e = new DataValidationException();
            $e->setValidationMessages(['bookingInfo' => 'Required but could not be found.']);
            throw $e;
        }

        $bookingInfo = $data['bookingInfo'];

        $messages = [];
        $availabilityFormatError = 'Invalid format. Expected ISO-8601 (eg. 2018-01-01T00:00:00+01:00).';

        if (isset($bookingInfo['availabilityStarts'])) {
            $dateTime = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $bookingInfo['availabilityStarts']);

            if (!$dateTime) {
                $messages['bookingInfo.availabilityStarts'] = $availabilityFormatError;
            }
        }

        if (isset($bookingInfo['availabilityEnds'])) {
            $dateTime = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $bookingInfo['availabilityEnds']);

            if (!$dateTime) {
                $messages['bookingInfo.availabilityEnds'] = $availabilityFormatError;
            }
        }

        if (isset($bookingInfo['urlLabel'])) {
            $errorMessage = 'Invalid format. ' .
                'Expected associative array with language codes as keys and translated strings as values.';

            if (!is_array($bookingInfo['urlLabel'])) {
                $messages['bookingInfo.urlLabel'] = $errorMessage;
            } else {
                try {
                    MultilingualString::deserialize($bookingInfo['urlLabel']);
                } catch (\Exception $e) {
                    $messages['bookingInfo.urlLabel'] = $errorMessage;
                }
            }
        }

        if (!empty($messages)) {
            $e = new DataValidationException();
            $e->setValidationMessages($messages);
            throw $e;
        }
    }
}
