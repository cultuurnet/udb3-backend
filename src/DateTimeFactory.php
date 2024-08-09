<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use DateTimeImmutable;
use DateTimeZone;

final class DateTimeFactory
{
    /**
     * Converts ISO-8601 datetime strings to DateTimeImmutable objects.
     * Use this as much as possible instead of doing DateTimeImmutable::createFromFormat() or new DateTimeImmutable()
     * when converting datetime strings, to avoid common bugs and to ensure correct error handling.
     */
    public static function fromISO8601(string $datetime): DateTimeImmutable
    {
        // Don't use the ISO8601 constant, as it is in fact not compatible with ISO-8601 according to the PHP docs.
        // See https://www.php.net/manual/en/class.datetimeinterface.php#datetime.constants.iso8601
        // The docs say to use ATOM instead, which is exactly the same as RFC3339.
        // We also accept RFC3339_EXTENDED, which is the same but includes milliseconds, to ensure better compatibility
        // with Javascript API clients.
        // We also accept "Y-m-d\TH:i:s.uP" which is the same as RFC3339_EXTENDED but with microseconds instead of
        // milliseconds ("u" instead of "v"). Because the RFC3339 docs do not specify the amount of possible decimals.
        // See https://datatracker.ietf.org/doc/html/rfc3339 for more info.
        $acceptedFormats = [
            DateTimeImmutable::RFC3339,
            DateTimeImmutable::RFC3339_EXTENDED,
            'Y-m-d\TH:i:s.uP',
        ];

        foreach ($acceptedFormats as $acceptedFormat) {
            $object = DateTimeImmutable::createFromFormat($acceptedFormat, $datetime);

            // $object can be FALSE if the given $datetime was not in the accepted format.
            // Only return it if it's actually a DateTimeImmutable, otherwise continue the loop.
            if ($object instanceof DateTimeImmutable) {
                return $object;
            }
        }

        // If we have not returned a DateTimeImmutable object by now, the $datetime string is in an unsupported format.
        // Throw a specific exception, so that it can be converted to a suitable ApiProblem higher up.
        throw new DateTimeInvalid($datetime . ' does not appear to be a valid ISO-8601 datetime string.');
    }

    public static function fromAtom(string $datetime): DateTimeImmutable
    {
        return self::fromFormat(DateTimeImmutable::ATOM, $datetime);
    }

    public static function fromFormat(string $format, string $datetime, DateTimeZone $timezone = null): DateTimeImmutable
    {
        $object = DateTimeImmutable::createFromFormat($format, $datetime, $timezone);

        if ($object instanceof DateTimeImmutable) {
            return $object;
        }

        throw new DateTimeInvalid($datetime . ' does not appear to be a valid ' . $format . ' datetime string.');
    }
}
