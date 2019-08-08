<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Calendar;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\Validators\StartDateEndDateValidator;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\Validators\TimeSpanValidator;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;

class CalendarForPlaceDataValidator implements DataValidatorInterface
{
    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $messages = [];

        $calendarJSONParser = new CalendarJSONParser();

        // For a place the following specific rules apply:
        // - Never time spans
        // - If a start date is given then an end date is also needed
        // - If an end date is given then a start date is also needed

        if ($calendarJSONParser->getTimeSpans($data)) {
            $messages['time_spans'] = 'No time spans allowed for place calendar.';
        }

        $messages = array_merge(
            $messages,
            (new StartDateEndDateValidator())->validate($data)
        );

        $messages = array_merge(
            $messages,
            (new TimeSpanValidator())->validate($data)
        );
        
        // All other combinations are valid:
        // - No data at all
        // - Start date and end date
        // - Opening hours
        // - Start date, end date and opening hours

        if (!empty($messages)) {
            $e = new DataValidationException();
            $e->setValidationMessages($messages);
            throw $e;
        }
    }
}
