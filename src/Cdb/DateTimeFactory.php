<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Cdb;

class DateTimeFactory
{
    /**
     * @param string $dateString
     * @return \DateTimeImmutable
     * @throws \InvalidArgumentException
     */
    public static function dateTimeFromDateString($dateString)
    {
        $date = \DateTimeImmutable::createFromFormat(
            'Y-m-d?H:i:s',
            $dateString,
            new \DateTimeZone('Europe/Brussels')
        );

        if (!$date instanceof \DateTimeImmutable) {
            throw new \InvalidArgumentException(
                'Value of argument $dateString is not convertable to a DateTimeImmutable object'
            );
        }

        return $date;
    }
}
