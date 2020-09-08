<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\Calendar\Validators\StartDateEndDateValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\Validators\TimeSpanValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;

class CalendarForEventDataValidator implements DataValidatorInterface
{
    /**
     * @param array $data
     * @throws DataValidationException
     */
    public function validate(array $data)
    {
        $messages = [];

        $calendarJSONParser = new CalendarJSONParser();

        // For an event the following specific rules apply:
        // - Empty data is not allowed
        // - If a start date is given then an end date is also needed
        // - If an end date is given then a start date is also needed
        // - When multiple time spans no opening hours

        if (count($data) === 0) {
            $messages['permanent'] = 'Permanent events are not supported.';
        }

        $messages = array_merge(
            $messages,
            (new TimeSpanValidator())->validate($data)
        );

        $timeSpans = $calendarJSONParser->getTimeSpans($data);

        // Single and multiple calendar types always have `timeSpans` from which the start and end date are dynamically
        // determined. If there are no timespans though, and a start and end date are given (ie. periodic calendar), the
        // start and end date SHOULD be validated.
        if (count($timeSpans) === 0) {
            $messages = array_merge(
                $messages,
                (new StartDateEndDateValidator())->validate($data)
            );
        }

        if (count($timeSpans) > 0 && count($calendarJSONParser->getOpeningHours($data)) > 0) {
            $messages['opening_hours'] = 'When opening hours are given no time spans are allowed.';
        }

        if (!empty($messages)) {
            $e = new DataValidationException();
            $e->setValidationMessages($messages);
            throw $e;
        }

        return true;
    }
}
