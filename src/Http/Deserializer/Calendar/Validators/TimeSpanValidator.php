<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Calendar\Validators;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use CultuurNet\UDB3\Http\Offer\UpdateStatusValidator;

class TimeSpanValidator
{
    public function validate(array $data): array
    {
        $messages = [];

        if (isset($data['timeSpans'])) {
            foreach ($data['timeSpans'] as $index => $timeSpan) {
                if (empty($timeSpan['start'])) {
                    $messages['start_' . $index] = 'A start is required for a time span.';
                }

                if (empty($timeSpan['end'])) {
                    $messages['end_' . $index] = 'An end is required for a time span.';
                }

                if (isset($timeSpan['status'])) {
                    try {
                        (new UpdateStatusValidator())->validate($timeSpan['status']);
                    } catch (DataValidationException $dataValidationException) {
                        $messages['status_' . $index] = $dataValidationException->getValidationMessages();
                    }
                }
            }
        }

        return $messages;
    }
}
