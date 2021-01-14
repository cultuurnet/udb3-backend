<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Deserializer\Calendar\Validators\StartDateEndDateValidator;
use CultuurNet\UDB3\Http\Deserializer\Calendar\Validators\ThrowIfNotEmpty;
use CultuurNet\UDB3\Http\Deserializer\Calendar\Validators\TimeSpanValidator;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Http\Offer\UpdateStatusValidator;

class CalendarForEventDataValidator implements DataValidatorInterface
{
    use ThrowIfNotEmpty;

    /**
     * @throws DataValidationException
     */
    public function validate(array $data): void
    {
        $messages = [];

        $calendarJSONParser = new CalendarJSONParser();

        // For an event the following specific rules apply:
        // - Empty data is not allowed
        // - If a status on the top level this should be in the correct format
        // - If a start date is given then an end date is also needed
        // - If an end date is given then a start date is also needed
        // - When multiple time spans no opening hours

        if (count($data) === 0) {
            $messages['permanent'] = 'Permanent events are not supported.';
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
            (new TimeSpanValidator())->validate($data)
        );

        // When the time spans contain errors it makes no sense to validate the rest of the data.
        if (!empty($messages)) {
            $this->throwIfNotEmpty($messages);
        }

        $timestamps = $calendarJSONParser->getTimestamps($data);

        // Single and multiple calendar types always have `timestamps` from which the start and end date are dynamically
        // determined. If there are no timestamps though, and a start and end date are given (ie. periodic calendar), the
        // start and end date SHOULD be validated.
        if (count($timestamps) === 0) {
            $messages = array_merge(
                $messages,
                (new StartDateEndDateValidator())->validate($data)
            );
        }

        if (count($timestamps) > 0 && count($calendarJSONParser->getOpeningHours($data)) > 0) {
            $messages['opening_hours'] = 'When opening hours are given no time spans are allowed.';
        }

        $this->throwIfNotEmpty($messages);
    }
}
