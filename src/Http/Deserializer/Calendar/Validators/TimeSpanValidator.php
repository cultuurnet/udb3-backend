<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar\Validators;

class TimeSpanValidator
{
    public function validate(array $data)
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
            }
        }

        return $messages;
    }
}
