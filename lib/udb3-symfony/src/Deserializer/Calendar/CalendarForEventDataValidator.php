<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Calendar;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\Validators\StartDateEndDateValidator;
use CultuurNet\UDB3\Symfony\Deserializer\Calendar\Validators\TimeSpanValidator;
use CultuurNet\UDB3\Symfony\Deserializer\DataValidator\DataValidatorInterface;

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
            (new StartDateEndDateValidator())->validate($data)
        );

        $messages = array_merge(
            $messages,
            (new TimeSpanValidator())->validate($data)
        );

        $timeSpans = $calendarJSONParser->getTimeSpans($data);
        if (count($timeSpans) > 0 && count($calendarJSONParser->getOpeningHours($data)) > 0) {
            $messages['opening_hours'] = 'When opening hours are given no time spans are allowed.';
        }

        if (count($timeSpans) > 0 &&
            $calendarJSONParser->getStartDate($data) &&
            $calendarJSONParser->getEndDate($data)) {
            if ($calendarJSONParser->getStartDate($data) != $timeSpans[0]->getStart()) {
                $messages['start_time_span'] = 'The start date is different from the start of the first time span.';
            }

            if ($calendarJSONParser->getEndDate($data) != $timeSpans[count($timeSpans) - 1]->getEnd()) {
                $messages['end_time_span'] = 'The end date is different from the end of the last time span.';
            }
        }

        if (!empty($messages)) {
            $e = new DataValidationException();
            $e->setValidationMessages($messages);
            throw $e;
        }

        return true;
    }
}
