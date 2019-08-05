<?php

namespace CultuurNet\UDB3\Symfony\Deserializer\Calendar\Validators;

use CultuurNet\UDB3\Symfony\Deserializer\Calendar\CalendarJSONParser;

class StartDateEndDateValidator
{
    public function validate(array $data)
    {
        $messages = [];

        $calendarJSONParser = new CalendarJSONParser();

        if ($calendarJSONParser->getEndDate($data) && !$calendarJSONParser->getStartDate($data)) {
            $messages['start_date'] = 'When an end date is given then a start date is also required.';
        }

        if ($calendarJSONParser->getStartDate($data) && !$calendarJSONParser->getEndDate($data)) {
            $messages['end_date'] = 'When a start date is given then an end date is also required.';
        }

        if ($calendarJSONParser->getEndDate($data) &&
            $calendarJSONParser->getStartDate($data) &&
            $calendarJSONParser->getEndDate($data) < $calendarJSONParser->getStartDate($data)) {
            $messages['start_end_date'] = 'The end date should be later then the start date.';
        }

        return $messages;
    }
}
