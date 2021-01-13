<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\Calendar\Validators\StartDateEndDateValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\Validators\TimeSpanValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Offer\UpdateStatusValidator;

class CalendarForPlaceDataValidator implements DataValidatorInterface
{
    /**
     * @throws DataValidationException
     */
    public function validate(array $data): void
    {
        $messages = [];

        $calendarJSONParser = new CalendarJSONParser();

        // For a place the following specific rules apply:
        // - Never timestamps
        // - If a status on the top level this should be in the correct format
        // - If a start date is given then an end date is also needed
        // - If an end date is given then a start date is also needed

        if ($calendarJSONParser->getTimestamps($data)) {
            $messages['time_spans'] = 'No time spans allowed for place calendar.';
        }

        if (isset($data['status'])) {
            try {
                (new UpdateStatusValidator())->validate($data['status']);
            } catch (DataValidationException $dataValidationException) {
                $messages['status'] = $dataValidationException->getValidationMessages();
            }
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
